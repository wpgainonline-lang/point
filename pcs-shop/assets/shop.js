(function () {
	'use strict';
	var $ = function (s, c) { return (c || document).querySelector(s); };
	var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
	var money = function (n) { return PCS.currency + Number(n).toLocaleString('en-IN'); };
	var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
	var nonce = PCS.nonce;

	function api(path, method, body) {
		var o = { method: method || 'GET', credentials: 'same-origin', headers: { 'X-WP-Nonce': nonce } };
		if (body) { o.headers['Content-Type'] = 'application/json'; o.body = JSON.stringify(body); }
		return fetch(PCS.api + path, o).then(function (r) {
			return r.json().then(function (j) {
				if (!r.ok) { throw new Error(j && j.message ? j.message : 'Something went wrong. Please try again.'); }
				return j;
			});
		});
	}

	/* ---------------- cart ---------------- */
	var KEY = 'pcs_cart';
	function load() { try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; } }
	function save(c) { try { localStorage.setItem(KEY, JSON.stringify(c)); } catch (e) {} badge(); }
	function badge() {
		var n = load().reduce(function (a, i) { return a + i.q; }, 0);
		$$('.cart-count').forEach(function (b) { b.textContent = n; b.hidden = !n; });
	}
	function add(id, g, cut, q) {
		var c = load(), f = c.filter(function (i) { return i.id === id && i.g === g && i.cut === cut; })[0];
		if (f) { f.q = Math.min(20, f.q + q); } else { c.push({ id: id, g: g, cut: cut || '', q: q }); }
		save(c);
		toast('Added to cart');
	}
	var tt;
	function toast(msg) {
		var t = $('#pcs-toast');
		if (!t) { t = document.createElement('div'); t.id = 'pcs-toast'; document.body.appendChild(t); }
		t.innerHTML = esc(msg) + ' · <a href="' + PCS.checkout + '">View cart</a>';
		t.className = 'show';
		clearTimeout(tt); tt = setTimeout(function () { t.className = ''; }, 2500);
	}

	document.addEventListener('click', function (e) {
		var b = e.target.closest('.js-add');
		if (!b) { return; }
		var card = b.closest('.card');
		if (card) {
			var d = JSON.parse(card.getAttribute('data-card'));
			add(d.id, parseInt($('.sel-w', card).value, 10), d.cuts[0] || '', 1);
			return;
		}
		var p = b.closest('.pdp');
		if (p) {
			var pd = JSON.parse(p.getAttribute('data-card'));
			var w = $('input[name=w]:checked', p), c = $('input[name=cut]:checked', p), q = $('#pdp-q', p);
			add(pd.id, parseInt(w.value, 10), c ? c.value : '', Math.max(1, Math.min(20, parseInt(q.value, 10) || 1)));
		}
	});
	document.addEventListener('change', function (e) {
		var s = e.target.closest('.sel-w');
		if (s) {
			var card = s.closest('.card'), d = JSON.parse(card.getAttribute('data-card'));
			$('.p-now', card).textContent = money(d.prices[d.weights.indexOf(parseInt(s.value, 10))]);
		}
		var w = e.target.closest('.pdp input[name=w]');
		if (w) {
			var p = w.closest('.pdp'), pd = JSON.parse(p.getAttribute('data-card'));
			$('#pdp-price', p).textContent = money(pd.prices[pd.weights.indexOf(parseInt(w.value, 10))]);
		}
	});

	/* ---------------- pincode check ---------------- */
	$$('.pin-check').forEach(function (f) {
		f.addEventListener('submit', function (e) {
			e.preventDefault();
			var pin = $('input', f).value, out = $('.msg', f);
			out.textContent = 'Checking...';
			api('pincode?pin=' + encodeURIComponent(pin)).then(function (r) {
				out.textContent = r.message; out.className = 'msg ' + (r.ok ? 'good' : 'bad');
				if (r.ok) { try { localStorage.setItem('pcs_pin', pin); } catch (x) {} }
			}).catch(function (er) { out.textContent = er.message; });
		});
	});

	/* ---------------- login ---------------- */
	var lg = $('#pcs-login');
	if (lg) {
		var msg = $('#lg-msg'), phone = '';
		var say = function (t, bad) { msg.textContent = t; msg.className = 'msg ' + (bad ? 'bad' : 'good'); };
		$('#lg-send').addEventListener('click', function () {
			phone = $('#lg-phone').value.replace(/\D/g, '');
			if (phone.length !== 10) { return say('Enter a valid 10-digit mobile number.', true); }
			var body = { phone: phone, name: $('#lg-name').value };
			if ($('#lg-email')) { body.email = $('#lg-email').value; }
			say('Sending code...');
			api('otp/send', 'POST', body).then(function () {
				$('.step-1', lg).hidden = true; $('.step-2', lg).hidden = false; say('Code sent. It is valid for 10 minutes.');
			}).catch(function (er) { say(er.message, true); });
		});
		$('#lg-verify').addEventListener('click', function () {
			say('Verifying...');
			api('otp/verify', 'POST', { phone: phone, code: $('#lg-code').value }).then(function () { location.reload(); })
				.catch(function (er) { say(er.message, true); });
		});
		$('#lg-back').addEventListener('click', function () { $('.step-1', lg).hidden = false; $('.step-2', lg).hidden = true; say(''); });
	}
	var lo = $('#pcs-logout');
	if (lo) { lo.addEventListener('click', function () { api('logout', 'POST').then(function () { location.href = '/'; }); }); }

	/* ---------------- contact ---------------- */
	var ct = $('#pcs-contact');
	if (ct) {
		ct.addEventListener('submit', function (e) {
			e.preventDefault();
			var o = {}; new FormData(ct).forEach(function (v, k) { o[k] = v; });
			var m = $('#ct-msg'); m.textContent = 'Sending...'; m.className = 'msg';
			api('contact', 'POST', o).then(function () { m.textContent = 'Thank you! We will get back to you shortly.'; m.className = 'msg good'; ct.reset(); })
				.catch(function (er) { m.textContent = er.message; m.className = 'msg bad'; });
		});
	}

	/* ---------------- checkout ---------------- */
	var co = $('#pcs-checkout');
	if (co) {
		var state = { coupon: '', q: null, slot: null, timer: null };
		var linesEl = $('#co-lines'), billEl = $('#co-bill'), emptyEl = $('#co-empty');

		function quote() {
			var cart = load();
			if (!cart.length) { render({ lines: [], subtotal: 0, discount: 0, delivery: 0, total: 0, min_order: 0 }); return; }
			api('quote', 'POST', { items: cart, coupon: state.coupon }).then(function (q) { state.q = q; render(q); })
				.catch(function (er) { $('#co-msg').textContent = er.message; });
		}
		function render(q) {
			emptyEl.hidden = q.lines.length > 0;
			linesEl.innerHTML = q.lines.map(function (l, i) {
				return '<div class="line' + (l.ok ? '' : ' off') + '">' + (l.img ? '<img src="' + esc(l.img) + '" alt="" width="56" height="56">' : '') +
					'<div class="grow"><strong>' + esc(l.name) + '</strong><div class="muted">' + esc(l.label) + (l.cut ? ' · ' + esc(l.cut) : '') + (l.ok ? '' : ' · unavailable') + '</div></div>' +
					'<div class="step"><button type="button" data-i="' + i + '" data-d="-1" aria-label="Less">−</button><span>' + l.q + '</span><button type="button" data-i="' + i + '" data-d="1" aria-label="More">+</button></div>' +
					'<div class="amt">' + money(l.total) + '</div></div>';
			}).join('');
			var rows = '<div><dt>Items</dt><dd>' + money(q.subtotal) + '</dd></div>';
			if (q.discount) { rows += '<div><dt>Coupon ' + esc(q.coupon) + '</dt><dd>−' + money(q.discount) + '</dd></div>'; }
			rows += '<div><dt>Delivery</dt><dd>' + (q.subtotal && !q.delivery ? 'Free' : money(q.delivery)) + '</dd></div>';
			rows += '<div class="tot"><dt>Total</dt><dd>' + money(q.total) + '</dd></div>';
			if (q.min_order && q.subtotal && q.subtotal < q.min_order) { rows += '<p class="msg bad">Minimum order ' + money(q.min_order) + '</p>'; }
			else if (q.free_above && q.subtotal && q.subtotal < q.free_above) { rows += '<p class="muted">Add ' + money(q.free_above - q.subtotal + q.discount) + ' more for free delivery</p>'; }
			billEl.innerHTML = rows;
			$('#co-cmsg').textContent = q.coupon_msg || '';
		}
		linesEl.addEventListener('click', function (e) {
			var b = e.target.closest('button[data-i]');
			if (!b || !state.q) { return; }
			var l = state.q.lines[+b.getAttribute('data-i')], c = load();
			c.forEach(function (i) { if (i.id === l.id && i.g === l.g && i.cut === l.cut) { i.q += +b.getAttribute('data-d'); } });
			save(c.filter(function (i) { return i.q > 0; }).map(function (i) { i.q = Math.min(20, i.q); return i; }));
			quote();
		});
		$('#co-apply').addEventListener('click', function () { state.coupon = $('#co-coupon').value.trim(); quote(); });

		var slotsEl = $('#co-slots');
		if (slotsEl) {
			var html = '';
			PCS.slots.forEach(function (d, di) {
				html += '<p class="day">' + esc(d.label) + '</p>';
				html += d.slots.length ? d.slots.map(function (s) { return '<label class="chip"><input type="radio" name="slot" value="' + esc(d.date + '|' + s) + '"><span>' + esc(s) + '</span></label>'; }).join('') : '<p class="muted">No more slots.</p>';
			});
			slotsEl.innerHTML = html;
			var first = $('input[name=slot]', slotsEl); if (first) { first.checked = true; }
		}
		var pin = $('#a-pin');
		if (pin) {
			try { var sp = localStorage.getItem('pcs_pin'); if (sp) { pin.value = sp; } } catch (x) {}
			pin.addEventListener('input', function () {
				var m = $('#a-pin-msg');
				if (pin.value.length === 6) { api('pincode?pin=' + pin.value).then(function (r) { m.textContent = r.message; m.className = 'msg ' + (r.ok ? 'good' : 'bad'); }); } else { m.textContent = ''; }
			});
		}
		var place = $('#co-place');
		if (place) {
			place.addEventListener('click', function () {
				var m = $('#co-msg'), s = $('input[name=slot]:checked'), pay = $('input[name=pay]:checked');
				if (!s) { m.textContent = 'Please choose a delivery slot.'; return; }
				var sv = s.value.split('|');
				place.disabled = true; m.textContent = 'Placing your order...'; m.className = 'msg';
				api('checkout', 'POST', {
					items: load(), coupon: state.coupon, payment: pay ? pay.value : 'cod', note: $('#a-note').value,
					slot: { date: sv[0], label: sv[1] },
					address: { name: $('#a-name').value, line: $('#a-line').value, landmark: $('#a-landmark').value, pincode: pin.value }
				}).then(function (r) { save([]); location.href = PCS.account + (PCS.account.indexOf('?') > -1 ? '&' : '?') + 'order=' + r.order; })
					.catch(function (er) { m.textContent = er.message; m.className = 'msg bad'; place.disabled = false; });
			});
		}
		quote();
	}
	$$('[data-slider]').forEach(function (sl) {
		var track = $('.hero', sl);
		['prev', 'next'].forEach(function (d) {
			var b = $('.sl-' + d, sl);
			if (b) { b.addEventListener('click', function () { track.scrollBy({ left: (d === 'next' ? 1 : -1) * track.clientWidth }); }); }
		});
	});
	$$('[data-car]').forEach(function (c) {
		var t = $('.car-track', c), prev = $('.sl-prev', c), next = $('.sl-next', c);
		function upd() { prev.disabled = t.scrollLeft < 4; next.disabled = t.scrollLeft + t.clientWidth >= t.scrollWidth - 4; }
		prev.addEventListener('click', function () { t.scrollBy({ left: -t.clientWidth * 0.85 }); });
		next.addEventListener('click', function () { t.scrollBy({ left: t.clientWidth * 0.85 }); });
		t.addEventListener('scroll', upd, { passive: true }); window.addEventListener('resize', upd); upd();
	});
	badge();
})();

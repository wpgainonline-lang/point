(function () {
	'use strict';
	var $ = function (s, c) { return (c || document).querySelector(s); };
	var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
	var money = function (n) { return PCS.currency + Number(n).toLocaleString('en-IN'); };
	var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };

	/* REST (login, pincode, contact) */
	function api(path, method, body) {
		var o = { method: method || 'GET', credentials: 'same-origin', headers: { 'X-WP-Nonce': PCS.nonce } };
		if (body) { o.headers['Content-Type'] = 'application/json'; o.body = JSON.stringify(body); }
		return fetch(PCS.api + path, o).then(function (r) {
			return r.json().then(function (j) {
				if (!r.ok) { throw new Error(j && j.message ? j.message : 'Something went wrong. Please try again.'); }
				return j;
			});
		});
	}
	/* WooCommerce ajax (cart) */
	function wc(action, data) {
		var o = { method: data ? 'POST' : 'GET', credentials: 'same-origin' };
		if (data) { var f = new URLSearchParams(); Object.keys(data).forEach(function (k) { f.append(k, data[k]); }); o.body = f; }
		return fetch(PCS.ajax + action, o).then(function (r) {
			return r.json().then(function (j) { if (!r.ok) { throw new Error(j && j.error ? j.error : 'Something went wrong. Please try again.'); } return j; });
		});
	}

	/* ---------------- cart count ---------------- */
	function badge(n) {
		try { localStorage.setItem('pcs_n', n); } catch (e) {}
		$$('.cart-count').forEach(function (b) { b.textContent = n; b.hidden = !n; });
	}
	try { var n0 = parseInt(localStorage.getItem('pcs_n'), 10); if (n0) { badge(n0); } } catch (e) {}
	wc('pcs_cart').then(function (p) { badge(p.count); }).catch(function () {});

	var tt;
	function toast(msg, link) {
		var t = $('#pcs-toast');
		if (!t) { t = document.createElement('div'); t.id = 'pcs-toast'; document.body.appendChild(t); }
		t.innerHTML = esc(msg) + (link ? ' · <a href="' + PCS.checkout + '" class="js-cart">View cart</a>' : '');
		t.className = 'show';
		clearTimeout(tt); tt = setTimeout(function () { t.className = ''; }, 2600);
	}

	function add(id, g, cut, q, btn) {
		if (btn) { btn.disabled = true; }
		wc('pcs_add', { id: id, g: g, cut: cut || '', q: q }).then(function (p) {
			badge(p.count);
			if (drawerOpen()) { paintDrawer(p); } else { toast('Added to cart', true); }
		}).catch(function (er) { toast(er.message); }).then(function () { if (btn) { btn.disabled = false; } });
	}

	document.addEventListener('click', function (e) {
		var b = e.target.closest('.js-add');
		if (!b) { return; }
		var card = b.closest('.card');
		if (card) {
			var d = JSON.parse(card.getAttribute('data-card'));
			add(d.id, parseInt($('.sel-w', card).value, 10), d.cuts[0] || '', 1, b);
			return;
		}
		var p = b.closest('.pdp');
		if (p) {
			var pd = JSON.parse(p.getAttribute('data-card'));
			var w = $('input[name=w]:checked', p), c = $('input[name=cut]:checked', p), q = $('#pdp-q', p);
			add(pd.id, parseInt(w.value, 10), c ? c.value : '', Math.max(1, Math.min(20, parseInt(q.value, 10) || 1)), b);
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

	/* ---------------- cart drawer ---------------- */
	var dr, lastFocus;
	var onCart = document.body.classList.contains('woocommerce-cart') || document.body.classList.contains('woocommerce-checkout');
	function drawerOpen() { return !!dr && dr.classList.contains('open'); }
	function buildDrawer() {
		dr = document.createElement('div'); dr.className = 'dr'; dr.hidden = true;
		dr.innerHTML = '<div class="dr-bg"></div><aside class="dr-panel" role="dialog" aria-modal="true" aria-label="Your cart"><div class="dr-head"><h2>Your cart</h2><button type="button" class="dr-x" aria-label="Close cart">×</button></div><div class="dr-body"></div><div class="dr-foot" hidden></div></aside>';
		document.body.appendChild(dr);
		$('.dr-bg', dr).addEventListener('click', closeDrawer);
		$('.dr-x', dr).addEventListener('click', closeDrawer);
		dr.addEventListener('click', function (e) {
			if (e.target.closest('.js-close')) { closeDrawer(); return; }
			var b = e.target.closest('button[data-key]');
			if (!b) { return; }
			wc('pcs_qty', { key: b.getAttribute('data-key'), q: b.getAttribute('data-q') }).then(function (p) { badge(p.count); paintDrawer(p); });
		});
		document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && drawerOpen()) { closeDrawer(); } });
	}
	function openDrawer() {
		if (!dr) { buildDrawer(); }
		lastFocus = document.activeElement;
		var tt2 = $('#pcs-toast'); if (tt2) { tt2.className = ''; }
		dr.hidden = false; void dr.offsetWidth; dr.classList.add('open');
		document.body.classList.add('lock');
		$('.dr-x', dr).focus();
		wc('pcs_cart').then(function (p) { badge(p.count); paintDrawer(p); }).catch(function (er) { $('.dr-body', dr).innerHTML = '<p class="msg bad">' + esc(er.message) + '</p>'; });
	}
	function closeDrawer() {
		dr.classList.remove('open'); document.body.classList.remove('lock');
		setTimeout(function () { if (!dr.classList.contains('open')) { dr.hidden = true; } }, 260);
		if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
	}
	function paintDrawer(p) {
		var body = $('.dr-body', dr), foot = $('.dr-foot', dr);
		if (p.empty) {
			body.innerHTML = '<div class="empty"><p>Your cart is empty.</p><a class="btn js-close" href="' + PCS.shop + '">Browse products</a></div>';
			foot.hidden = true; return;
		}
		body.innerHTML = p.lines;
		foot.innerHTML = p.foot + (p.below ? '<button type="button" class="btn btn-block" disabled>Add ' + money(p.need) + ' more to checkout</button>' : '<a class="btn btn-block" href="' + PCS.checkout + '">Checkout</a>') + '<button type="button" class="btn btn-line btn-block js-close">Continue shopping</button>';
		foot.hidden = false;
	}
	document.addEventListener('click', function (e) {
		var a = e.target.closest('.js-cart');
		if (!a || onCart || e.ctrlKey || e.metaKey || e.shiftKey) { return; }
		e.preventDefault(); openDrawer();
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
	// Checkout: remember and check the pincode as it is typed
	document.addEventListener('input', function (e) {
		if (e.target.id !== 'billing_postcode') { return; }
		var v = e.target.value.replace(/\D/g, ''), m = $('#pcs-pin-msg');
		if (!m) { m = document.createElement('p'); m.id = 'pcs-pin-msg'; m.className = 'msg'; e.target.parentNode.appendChild(m); }
		if (v.length === 6) {
			var ok = Object.prototype.hasOwnProperty.call(PCS.pins, v);
			m.textContent = ok ? 'We deliver here.' : 'Sorry, we do not deliver to this pincode yet.'; m.className = 'msg ' + (ok ? 'good' : 'bad');
			if (ok) { try { localStorage.setItem('pcs_pin', v); } catch (x) {} }
		} else { m.textContent = ''; }
	});
	document.addEventListener('DOMContentLoaded', function () {
		var pc = $('#billing_postcode');
		if (pc && !pc.value) { try { var sp = localStorage.getItem('pcs_pin'); if (sp) { pc.value = sp; } } catch (x) {} }
	});

	/* ---------------- login (OTP) ---------------- */
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

	/* ---------------- sliders ---------------- */
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
})();

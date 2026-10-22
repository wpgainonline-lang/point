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

	/* "Added to cart" pop-up with View cart + Checkout */
	var popT;
	function popup(info, p) {
		var el = $('#pcs-pop');
		if (!el) {
			el = document.createElement('div'); el.id = 'pcs-pop'; el.setAttribute('role', 'status');
			document.body.appendChild(el);
			el.addEventListener('mouseenter', function () { clearTimeout(popT); });
			el.addEventListener('mouseleave', function () { popT = setTimeout(hidePop, 3000); });
			el.addEventListener('click', function (e) { if (e.target.closest('.pp-x') || e.target.closest('.js-cart')) { hidePop(); } });
		}
		el.innerHTML = '<button type="button" class="pp-x" aria-label="Close">×</button>' +
			'<div class="pp-row">' + (info.img ? '<img src="' + esc(info.img) + '" alt="" width="64" height="64">' : '<span class="pp-ok">✓</span>') +
			'<div class="pp-txt"><strong>✓ Added to cart</strong><span class="pp-nm">' + esc(info.name) + (info.label ? ' · ' + esc(info.label) : '') + '</span>' +
			'<span class="pp-sum">' + p.count + ' item' + (p.count === 1 ? '' : 's') + ' in cart · ' + esc(p.sub) + '</span></div></div>' +
			'<div class="pp-btns"><a class="btn btn-line js-cart" href="' + PCS.checkout + '">View cart</a><a class="btn" href="' + PCS.checkout + '">Checkout</a></div>';
		void el.offsetWidth; el.className = 'show';
		clearTimeout(popT); popT = setTimeout(hidePop, 6500);
	}
	function hidePop() { var el = $('#pcs-pop'); if (el) { el.className = ''; } }

	function add(id, g, cut, q, btn, info) {
		if (btn) { btn.disabled = true; }
		wc('pcs_add', { id: id, g: g, cut: cut || '', q: q }).then(function (p) {
			badge(p.count);
			if (drawerOpen()) { paintDrawer(p); } else { popup(info || { name: 'Item' }, p); }
		}).catch(function (er) { toast(er.message); }).then(function () { if (btn) { btn.disabled = false; } });
	}

	document.addEventListener('click', function (e) {
		var b = e.target.closest('.js-add');
		if (!b) { return; }
		var card = b.closest('.card');
		if (card) {
			var d = JSON.parse(card.getAttribute('data-card'));
			var sel = $('.sel-w', card), im = $('.card-img img', card);
			add(d.id, parseInt(sel.value, 10), d.cuts[0] || '', 1, b, { name: d.name, label: sel.options[sel.selectedIndex].text, img: im ? im.src : '' });
			return;
		}
		var p = b.closest('.pdp');
		if (p) {
			var pd = JSON.parse(p.getAttribute('data-card'));
			var w = $('input[name=w]:checked', p), c = $('input[name=cut]:checked', p), q = $('#pdp-q', p);
			var pim = $('.pdp-img img', p);
			add(pd.id, parseInt(w.value, 10), c ? c.value : '', Math.max(1, Math.min(20, parseInt(q.value, 10) || 1)), b, { name: pd.name, label: $('span', w.parentNode).textContent + (c ? ' · ' + c.value : ''), img: pim ? pim.src : '' });
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
			var wi = pd.weights.indexOf(parseInt(w.value, 10)), mr = $('#pdp-mrp', p);
			$('#pdp-price', p).textContent = money(pd.prices[wi]);
			if (mr && pd.mrps) { mr.textContent = money(pd.mrps[wi]); }
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
		hidePop();
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

	/* ---------------- login: password, OTP, register ---------------- */
	var lg = $('#pcs-login');
	if (lg) {
		var msg = $('#lg-msg');
		var say = function (t, bad) { msg.textContent = t; msg.className = 'msg ' + (bad ? 'bad' : 'good'); };
		var digits = function (v) { return v.replace(/\D/g, ''); };
		var done = function () { location.reload(); };

		// switch between the password form, the OTP form and the register form
		lg.addEventListener('click', function (e) {
			var t = e.target.closest('[data-show]');
			if (t) {
				var w = t.getAttribute('data-show');
				if (w === 'lg-reg') { $('#nc-intro').hidden = true; $('#lg-reg').hidden = false; $('#lr-name').focus(); }
				else { $('#lg-pw').hidden = w !== 'lg-pw'; $('#lg-otp').hidden = w !== 'lg-otp'; }
				say('');
				return;
			}
			var eye = e.target.closest('[data-eye]');
			if (eye) {
				var inp = $('input', eye.parentNode), show = inp.type === 'password';
				inp.type = show ? 'text' : 'password'; eye.textContent = show ? 'Hide' : 'Show';
			}
		});

		// password login
		$('#lg-pw').addEventListener('submit', function (e) {
			e.preventDefault();
			var id = $('#lp-id').value.trim(), pw = $('#lp-pw').value;
			if (!id || !pw) { return say('Enter your mail id / mobile number and password.', true); }
			say('Logging in...');
			api('auth/login', 'POST', { id: id, password: pw }).then(done).catch(function (er) { say(er.message, true); });
		});

		// OTP login
		var otpPhone = '';
		$('#lg-otp').addEventListener('submit', function (e) {
			e.preventDefault();
			if (!$('.step-2', $('#lg-otp')).hidden) { return $('#lo-verify').click(); }
			otpPhone = digits($('#lo-phone').value);
			if (otpPhone.length !== 10) { return say('Enter a valid 10-digit mobile number.', true); }
			var body = { phone: otpPhone };
			if ($('#lo-email')) { body.email = $('#lo-email').value; }
			say('Sending code...');
			api('otp/send', 'POST', body).then(function () {
				$('#lg-otp .step-1').hidden = true; $('#lg-otp .step-2').hidden = false; $('#lo-code').focus(); say('Code sent. It is valid for 10 minutes.');
			}).catch(function (er) { say(er.message, true); });
		});
		$('#lo-verify').addEventListener('click', function () {
			say('Verifying...');
			api('otp/verify', 'POST', { phone: otpPhone, code: $('#lo-code').value }).then(done).catch(function (er) { say(er.message, true); });
		});

		// register (mobile is verified by OTP)
		var regPhone = '';
		$('#lg-reg').addEventListener('submit', function (e) {
			e.preventDefault();
			if (!$('#lg-reg .step-2').hidden) { return $('#lr-verify').click(); }
			regPhone = digits($('#lr-phone').value);
			var name = $('#lr-name').value.trim(), pw = $('#lr-pw').value;
			if (name.length < 2) { return say('Please enter your name.', true); }
			if (regPhone.length !== 10) { return say('Enter a valid 10-digit mobile number.', true); }
			if (pw.length < 6) { return say('Password must be at least 6 characters.', true); }
			say('Sending code...');
			api('otp/send', 'POST', { register: 1, name: name, phone: regPhone, email: $('#lr-email').value, password: pw }).then(function () {
				$('#lg-reg .step-1').hidden = true; $('#lg-reg .step-2').hidden = false; $('#lr-code').focus(); say('Code sent. It is valid for 10 minutes.');
			}).catch(function (er) { say(er.message, true); });
		});
		$('#lr-verify').addEventListener('click', function () {
			say('Creating your account...');
			api('otp/verify', 'POST', { phone: regPhone, code: $('#lr-code').value }).then(done).catch(function (er) { say(er.message, true); });
		});
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


	/* ---------------- search panel ---------------- */
	var sbar = $('#sbar');
	$$('[data-search-toggle]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			var open = sbar.hidden;
			sbar.hidden = !open;
			$$('[data-search-toggle]').forEach(function (b) { b.setAttribute('aria-expanded', open); });
			if (open) { $('#hd-q').focus(); }
		});
	});
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && sbar && !sbar.hidden && !drawerOpen()) { sbar.hidden = true; } });

	/* ---------------- header search suggestions ---------------- */
	var sf = $('.hd-search');
	if (sf) {
		var sq = $('#hd-q', sf), sg = $('#hd-sug', sf), st, sreq = 0, active = -1;
		var shut = function () { sg.hidden = true; sq.setAttribute('aria-expanded', 'false'); active = -1; };
		var items = function () { return $$('a', sg); };
		var mark = function () { items().forEach(function (a, i) { a.classList.toggle('on', i === active); a.setAttribute('aria-selected', i === active); }); };
		sq.addEventListener('input', function () {
			clearTimeout(st);
			var v = sq.value.trim();
			if (v.length < 2) { shut(); return; }
			st = setTimeout(function () {
				var id = ++sreq;
				api('search?q=' + encodeURIComponent(v)).then(function (r) {
					if (id !== sreq) { return; }
					var h = r.map(function (p) {
						return '<a role="option" href="' + esc(p.url) + '">' + (p.img ? '<img src="' + esc(p.img) + '" alt="" width="40" height="40">' : '<span class="ph"></span>') + '<span class="nm">' + esc(p.name) + '</span><span class="pr">' + esc(p.price) + '</span></a>';
					}).join('');
					if (!r.length) { h = '<p class="none">No products found for “' + esc(v) + '”.</p>'; }
					else { h += '<a role="option" class="all" href="' + esc(sf.action + '?s=' + encodeURIComponent(v) + '&post_type=product') + '">See all results for “' + esc(v) + '”</a>'; }
					sg.innerHTML = h; sg.hidden = false; sq.setAttribute('aria-expanded', 'true'); active = -1;
				}).catch(shut);
			}, 220);
		});
		sq.addEventListener('keydown', function (e) {
			var it = items();
			if (e.key === 'Escape') { shut(); }
			else if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && it.length && !sg.hidden) {
				e.preventDefault(); active = (active + (e.key === 'ArrowDown' ? 1 : -1) + it.length) % it.length; mark();
			} else if (e.key === 'Enter' && active > -1 && it[active]) { e.preventDefault(); location.href = it[active].href; }
		});
		document.addEventListener('click', function (e) { if (!sf.contains(e.target)) { shut(); } });
	}

	/* ---------------- promo strip: slide + zoom + shine, with small dots ---------------- */
	$$('[data-fade]').forEach(function (box) {
		var items = $$('a', box), every = parseInt(box.getAttribute('data-fade'), 10) || 4500, cur = 0, hold = false;
		if (items.length < 2) { return; }
		var dots = document.createElement('span'); dots.className = 'pf-dots'; dots.setAttribute('aria-hidden', 'true');
		items.forEach(function (_, i) { var d = document.createElement('i'); if (!i) { d.className = 'on'; } dots.appendChild(d); });
		box.appendChild(dots);
		if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
		function show(n) {
			var prev = items[cur];
			prev.classList.remove('on'); prev.classList.add('out'); prev.setAttribute('tabindex', '-1'); prev.setAttribute('aria-hidden', 'true');
			setTimeout(function () { prev.classList.remove('out'); }, 1000);
			cur = n; items[cur].classList.add('on'); items[cur].removeAttribute('tabindex'); items[cur].removeAttribute('aria-hidden');
			$$('i', dots).forEach(function (d, i) { d.className = i === cur ? 'on' : ''; });
			box.classList.remove('shine'); void box.offsetWidth; box.classList.add('shine');
		}
		setInterval(function () { if (!hold && !document.hidden) { show((cur + 1) % items.length); } }, every);
		['mouseenter', 'focusin'].forEach(function (ev) { box.addEventListener(ev, function () { hold = true; }); });
		['mouseleave', 'focusout'].forEach(function (ev) { box.addEventListener(ev, function () { hold = false; }); });
	});

	/* ---------------- sliders ---------------- */
	$$('[data-slider]').forEach(function (sl) {
		var track = $('.hero', sl);
		['prev', 'next'].forEach(function (d) {
			var b = $('.sl-' + d, sl);
			if (b) { b.addEventListener('click', function () { track.scrollBy({ left: (d === 'next' ? 1 : -1) * track.clientWidth }); }); }
		});
		var every = parseInt(sl.getAttribute('data-auto'), 10);
		if (every && track.children.length > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			var hold = false;
			setInterval(function () {
				if (hold || document.hidden) { return; }
				if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 4) { track.scrollTo({ left: 0 }); } else { track.scrollBy({ left: track.clientWidth }); }
			}, every);
			['mouseenter', 'focusin', 'touchstart'].forEach(function (ev) { sl.addEventListener(ev, function () { hold = true; }, { passive: true }); });
			['mouseleave', 'focusout'].forEach(function (ev) { sl.addEventListener(ev, function () { hold = false; }); });
			sl.addEventListener('touchend', function () { setTimeout(function () { hold = false; }, 5000); }, { passive: true });
		}
	});
	$$('[data-car]').forEach(function (c) {
		var t = $('.car-track', c), prev = $('.sl-prev', c), next = $('.sl-next', c);
		function upd() { prev.disabled = t.scrollLeft < 4; next.disabled = t.scrollLeft + t.clientWidth >= t.scrollWidth - 4; }
		prev.addEventListener('click', function () { t.scrollBy({ left: -t.clientWidth * 0.85 }); });
		next.addEventListener('click', function () { t.scrollBy({ left: t.clientWidth * 0.85 }); });
		t.addEventListener('scroll', upd, { passive: true }); window.addEventListener('resize', upd); upd();

		// dots (one per card) and gentle auto-scroll for carousels that ask for it
		var dotsBox = c.parentNode.querySelector('.car-dots');
		var cards = t.children;
		var gap = function () { return parseFloat(getComputedStyle(t).columnGap) || 0; };
		var stepW = function () { return cards[0].getBoundingClientRect().width + gap(); };
		var idx = function () { return Math.min(cards.length - 1, Math.round(t.scrollLeft / stepW())); };
		if (dotsBox && cards.length > 1) {
			for (var i = 0; i < cards.length; i++) { var d = document.createElement('button'); d.type = 'button'; d.setAttribute('aria-label', 'Go to review ' + (i + 1)); d.dataset.i = i; dotsBox.appendChild(d); }
			dotsBox.removeAttribute('aria-hidden');
			dotsBox.addEventListener('click', function (e) { var b = e.target.closest('button'); if (b) { t.scrollTo({ left: b.dataset.i * stepW() }); } });
			var mark = function () { var k = t.scrollLeft + t.clientWidth >= t.scrollWidth - 4 ? cards.length - 1 : idx(); $$('button', dotsBox).forEach(function (b, j) { b.className = j === k ? 'on' : ''; }); };
			t.addEventListener('scroll', mark, { passive: true }); mark();
		}
		var every = parseInt(c.getAttribute('data-auto'), 10);
		if (every && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			var hold = false;
			var tick = function () {
				if (hold || document.hidden) { return; }
				if (t.scrollLeft + t.clientWidth >= t.scrollWidth - 4) { t.scrollTo({ left: 0 }); } else { t.scrollBy({ left: stepW() }); }
			};
			var timer = setInterval(tick, every);
			['mouseenter', 'focusin', 'touchstart'].forEach(function (ev) { c.addEventListener(ev, function () { hold = true; }, { passive: true }); });
			['mouseleave', 'focusout'].forEach(function (ev) { c.addEventListener(ev, function () { hold = false; }); });
			c.addEventListener('touchend', function () { setTimeout(function () { hold = false; }, 6000); }, { passive: true });
		}
	});
})();

/* Deal countdown */
(function () {
	var el = document.querySelector('[data-countdown]');
	if (!el) { return; }
	var end = +el.getAttribute('data-countdown'), off = +el.getAttribute('data-now') - Math.floor(Date.now() / 1000);
	function pad(n) { return (n < 10 ? '0' : '') + n; }
	function tick() {
		var left = Math.max(0, end - (Math.floor(Date.now() / 1000) + off));
		var v = { d: Math.floor(left / 86400), h: Math.floor(left % 86400 / 3600), m: Math.floor(left % 3600 / 60), s: left % 60 };
		for (var k in v) { var b = el.querySelector('[data-u=' + k + ']'); if (b) { b.textContent = pad(v[k]); } }
	}
	tick(); setInterval(tick, 1000);
})();

/* Product page photo thumbnails */
document.addEventListener('click', function (e) {
	var t = e.target.closest('.pdp-thumbs button');
	if (!t) { return; }
	var m = document.getElementById('pdp-main');
	if (m) { m.src = t.getAttribute('data-full'); m.removeAttribute('srcset'); m.removeAttribute('sizes'); }
	var on = document.querySelector('.pdp-thumbs .on'); if (on) { on.classList.remove('on'); }
	t.classList.add('on');
});

/* Product page quantity stepper */
document.addEventListener('click', function (e) {
	var b = e.target.closest('.pdp-buy .qm, .pdp-buy .qp');
	if (!b) { return; }
	var q = document.getElementById('pdp-q'), v = parseInt(q.value, 10) || 1;
	q.value = Math.max(1, Math.min(20, v + (b.classList.contains('qp') ? 1 : -1)));
});

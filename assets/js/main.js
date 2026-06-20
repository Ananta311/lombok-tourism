// ============================================
// Lombok Tourism - Main JavaScript
// ============================================

document.addEventListener('DOMContentLoaded', function () {

    // ============ NAVBAR SCROLL ============
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 40);
        });
    }

    // ============ NAV TOGGLE (mobile) ============
    const navToggle = document.getElementById('navToggle');
    const navMenu   = document.getElementById('navMenu');
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('open');
            navToggle.classList.toggle('open');
        });
        document.addEventListener('click', (e) => {
            if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.classList.remove('open');
                navToggle.classList.remove('open');
            }
        });
    }

    // ============ BACK TO TOP ============
    const btt = document.getElementById('backToTop');
    if (btt) {
        window.addEventListener('scroll', () => btt.classList.toggle('show', window.scrollY > 300));
        btt.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }

    // ============ SCROLL REVEAL ============
    const reveals = document.querySelectorAll('[data-reveal]');
    if (reveals.length) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((e, i) => {
                if (e.isIntersecting) {
                    const delay = e.target.dataset.delay || 0;
                    setTimeout(() => e.target.classList.add('revealed'), delay * 100);
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.12 });
        reveals.forEach(el => io.observe(el));
    }

    // ============ ALERT AUTO DISMISS ============
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 4000);
    });

    // ============ PASSWORD TOGGLE ============
    document.querySelectorAll('.input-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const inp = btn.previousElementSibling;
            if (!inp) return;
            const isPass = inp.type === 'password';
            inp.type = isPass ? 'text' : 'password';
            btn.innerHTML = isPass ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        });
    });

    // ============ FILE UPLOAD PREVIEW ============
    document.querySelectorAll('.file-upload-area').forEach(area => {
        const input = area.querySelector('input[type=file]');
        const preview = area.parentElement.querySelector('.preview-grid');
        if (!input) return;

        area.addEventListener('click', () => input.click());
        area.addEventListener('dragover', e => { e.preventDefault(); area.classList.add('dragover'); });
        area.addEventListener('dragleave', () => area.classList.remove('dragover'));
        area.addEventListener('drop', e => {
            e.preventDefault(); area.classList.remove('dragover');
            showPreviews(e.dataTransfer.files, preview);
        });

        input.addEventListener('change', () => showPreviews(input.files, preview));
    });

    function showPreviews(files, container) {
        if (!container) return;
        container.innerHTML = '';
        Array.from(files).forEach(file => {
            if (!file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = e => {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `<img src="${e.target.result}" alt="preview">
                    <button type="button" class="preview-remove" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>`;
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    // ============ STAR RATING INPUT ============
    document.querySelectorAll('.star-rating').forEach(wrap => {
        const stars = wrap.querySelectorAll('i');
        const input = wrap.nextElementSibling;

        stars.forEach((star, idx) => {
            star.addEventListener('mouseenter', () => {
                stars.forEach((s, i) => s.classList.toggle('hover', i <= idx));
            });
            star.addEventListener('mouseleave', () => {
                stars.forEach(s => s.classList.remove('hover'));
            });
            star.addEventListener('click', () => {
                stars.forEach((s, i) => s.classList.toggle('active', i <= idx));
                if (input) input.value = idx + 1;
            });
        });

        wrap.addEventListener('mouseleave', () => {
            stars.forEach(s => s.classList.remove('hover'));
        });
    });

    // ============ GALLERY THUMBNAIL ============
    const galleryMain  = document.querySelector('.gallery-main img');
    const galleryThumbs = document.querySelectorAll('.gallery-thumb');
    if (galleryMain && galleryThumbs.length) {
        galleryThumbs.forEach(thumb => {
            thumb.addEventListener('click', () => {
                galleryMain.src = thumb.querySelector('img').src;
                galleryThumbs.forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
            });
        });
        if (galleryThumbs[0]) galleryThumbs[0].classList.add('active');
    }

    // ============ LIGHTBOX ============
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    if (lightbox && lightboxImg) {
        document.querySelectorAll('[data-lightbox]').forEach(el => {
            el.addEventListener('click', () => {
                lightboxImg.src = el.dataset.lightbox || el.src || el.querySelector('img')?.src;
                lightbox.classList.add('open');
                document.body.style.overflow = 'hidden';
            });
        });
        document.getElementById('lightboxClose')?.addEventListener('click', closeLightbox);
        lightbox.addEventListener('click', e => { if (e.target === lightbox) closeLightbox(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
    }
    function closeLightbox() {
        lightbox?.classList.remove('open');
        document.body.style.overflow = '';
    }

    // ============ COMMENT PHOTO PREVIEW ============
    const commentPhotoInput = document.getElementById('commentPhotos');
    const commentPreview    = document.getElementById('commentPhotoPreview');
    if (commentPhotoInput && commentPreview) {
        commentPhotoInput.addEventListener('change', () => {
            commentPreview.innerHTML = '';
            Array.from(commentPhotoInput.files).slice(0, 5).forEach(f => {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.createElement('div');
                    img.className = 'comment-photo';
                    img.innerHTML = `<img src="${e.target.result}">`;
                    commentPreview.appendChild(img);
                };
                reader.readAsDataURL(f);
            });
        });
    }

    // ============ SEARCH SUGGESTIONS (live filter) ============
    const searchInput = document.getElementById('searchWisata');
    if (searchInput) {
        searchInput.addEventListener('keypress', e => {
            if (e.key === 'Enter') {
                const q = searchInput.value.trim();
                if (q) window.location.href = `wisata.php?q=${encodeURIComponent(q)}`;
            }
        });
    }

    // ============ ADMIN: IMAGE BG PREVIEW ============
    const bgFileInput = document.getElementById('bgFileInput');
    const bgPreview   = document.getElementById('bgPreview');
    if (bgFileInput && bgPreview) {
        bgFileInput.addEventListener('change', function () {
            const f = this.files[0];
            if (f) {
                const reader = new FileReader();
                reader.onload = e => {
                    bgPreview.style.backgroundImage = `url('${e.target.result}')`;
                    bgPreview.innerHTML = '';
                };
                reader.readAsDataURL(f);
            }
        });
    }

    // ============ ADMIN SIDEBAR TOGGLE (mobile) ============
    const adminSidebarToggle = document.getElementById('adminSidebarToggle');
    const adminSidebar = document.querySelector('.admin-sidebar');
    if (adminSidebarToggle && adminSidebar) {
        adminSidebarToggle.addEventListener('click', () => adminSidebar.classList.toggle('open'));
    }

    // ============ NUMBER COUNTER ANIMATION ============
    function animateCounter(el) {
        const target = parseInt(el.dataset.count);
        const duration = 1400;
        const step = target / (duration / 16);
        let current = 0;
        const timer = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = Math.floor(current).toLocaleString('id-ID') + (el.dataset.suffix || '');
            if (current >= target) clearInterval(timer);
        }, 16);
    }
    const counterObserver = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { animateCounter(e.target); counterObserver.unobserve(e.target); } });
    });
    document.querySelectorAll('[data-count]').forEach(el => counterObserver.observe(el));

    
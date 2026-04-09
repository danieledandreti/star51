// Inject lightbox HTML
(function() {
  var html = '<div class="lightbox" id="lightbox">'
    + '<button class="lightbox-close" aria-label="Close"><i class="bi bi-x-lg"></i></button>'
    + '<button class="lightbox-arrow lightbox-prev" aria-label="Previous"><i class="bi bi-chevron-left"></i></button>'
    + '<div class="lightbox-content"><img src="" alt="" id="lightbox-img"><p class="lightbox-caption" id="lightbox-caption"></p></div>'
    + '<button class="lightbox-arrow lightbox-next" aria-label="Next"><i class="bi bi-chevron-right"></i></button>'
    + '</div>';
  document.body.insertAdjacentHTML('beforeend', html);
})();

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(function(link) {
  link.addEventListener('click', function(e) {
    var target = document.querySelector(this.getAttribute('href'));
    if (!target) return;
    e.preventDefault();
    var start = window.scrollY;
    var end = target.getBoundingClientRect().top + window.scrollY - 80;
    var distance = end - start;
    var duration = 1200;
    var startTime = null;
    function step(time) {
      if (!startTime) startTime = time;
      var progress = Math.min((time - startTime) / duration, 1);
      window.scrollTo(0, start + distance * progress);
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  });
});

// Carousel scroll
(function() {
  var track = document.querySelector('.carousel-track');
  var prev = document.querySelector('.carousel-prev');
  var next = document.querySelector('.carousel-next');
  var scrollAmount = 200;

  prev.addEventListener('click', function() {
    track.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
  });
  next.addEventListener('click', function() {
    track.scrollBy({ left: scrollAmount, behavior: 'smooth' });
  });
})();

// Lightbox
(function() {
  var lightbox = document.getElementById('lightbox');
  var lbImg = document.getElementById('lightbox-img');
  var lbCaption = document.getElementById('lightbox-caption');
  var slides = document.querySelectorAll('.carousel-slide');
  var current = 0;
  var total = slides.length;

  function open(index) {
    current = index;
    update();
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function close() {
    lightbox.classList.remove('active');
    document.body.style.overflow = '';
  }

  function update() {
    var slide = slides[current];
    var img = slide.querySelector('img');
    lbImg.src = img.src;
    lbImg.alt = img.alt;
    lbCaption.textContent = slide.querySelector('.carousel-caption').textContent;
  }

  function navigate(dir) {
    current = (current + dir + total) % total;
    update();
  }

  slides.forEach(function(slide) {
    slide.addEventListener('click', function() {
      open(parseInt(this.dataset.index));
    });
  });

  document.querySelector('.lightbox-close').addEventListener('click', close);
  document.querySelector('.lightbox-prev').addEventListener('click', function() { navigate(-1); });
  document.querySelector('.lightbox-next').addEventListener('click', function() { navigate(1); });

  lightbox.addEventListener('click', function(e) {
    if (e.target === lightbox) close();
  });

  document.addEventListener('keydown', function(e) {
    if (!lightbox.classList.contains('active')) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft') navigate(-1);
    if (e.key === 'ArrowRight') navigate(1);
  });
})();

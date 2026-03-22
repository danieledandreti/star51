/**
 * Star51.js - Star51 Frontend JavaScript
 * Card entrance animation (cards hidden by CSS, revealed by JS)
 */

document.addEventListener('DOMContentLoaded', function () {
  // Staggered reveal animation (cards start hidden via CSS)
  const cards = document.querySelectorAll('.star51-card, .news-card');

  cards.forEach(function (card, index) {
    setTimeout(function () {
      card.classList.add('star51-card-visible');
    }, index * 100);
  });
});

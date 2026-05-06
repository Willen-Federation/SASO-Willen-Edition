(function () {
  'use strict';

  function initBarcodeSearch() {
    var input = document.querySelector('#barcodeInput');
    var submit = document.querySelector('#barcodeSubmit');
    if (!input || !submit) return;

    function update(value) {
      var fullCode = String(value || '').replace(/\D/g, '').slice(0, 12);
      if (input.value !== fullCode) input.value = fullCode;

      if (fullCode.length !== 12) {
        submit.setAttribute('href', '');
        submit.setAttribute('aria-disabled', 'true');
        submit.classList.add('disabled');
        return;
      }

      submit.setAttribute(
        'href',
        './item/start/item/' + fullCode.slice(0, 8) +
          '/color/' + fullCode.slice(8, 10) +
          '/size/' + fullCode.slice(10, 12) +
          '/action/shelf'
      );
      submit.setAttribute('aria-disabled', 'false');
      submit.classList.remove('disabled');
    }

    update(input.value);
    input.addEventListener('input', function (event) {
      update(event.target.value);
    });
    input.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter') return;
      var href = submit.getAttribute('href');
      if (!href) return;
      event.preventDefault();
      location.href = href;
    });
    submit.addEventListener('click', function (event) {
      if (submit.getAttribute('aria-disabled') !== 'true') return;
      event.preventDefault();
      input.focus();
    });
  }

  function initKeywordSearch() {
    var input = document.querySelector('#keywordInput');
    var submit = document.querySelector('#keywordSubmit');
    if (!input || !submit) return;

    function search() {
      var keyword = String(input.value || '').trim();
      if (!keyword) return;
      location.href = './search/start/search/' + encodeURIComponent(keyword.replace(/\//g, ''));
    }

    submit.addEventListener('click', search);
    input.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter') return;
      event.preventDefault();
      search();
    });
  }

  function initListSearch() {
    var input = document.querySelector('#search');
    var submit = document.querySelector('#searchButton');
    var current = document.querySelector('#current');
    if (!input || !submit || !current) return;

    function jump() {
      var keyword = String(input.value || '').trim().replace(/\//g, '');
      location.href = './' + current.textContent + (keyword ? '/search/' + encodeURIComponent(keyword) : '');
    }

    input.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter') return;
      event.preventDefault();
      jump();
    });
    submit.addEventListener('click', jump);
  }

  function init() {
    initBarcodeSearch();
    initKeywordSearch();
    initListSearch();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

(function() {
    'use strict';

    document.querySelectorAll('.ssm-copy').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var url = btn.closest('.ssm-buttons').getAttribute('data-url');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    var original = btn.querySelector('.ssm-label').textContent;
                    btn.querySelector('.ssm-label').textContent = 'Copied!';
                    setTimeout(function() {
                        btn.querySelector('.ssm-label').textContent = original;
                    }, 1500);
                });
            } else {
                var input = document.createElement('input');
                input.value = url;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
            }
        });
    });
})();

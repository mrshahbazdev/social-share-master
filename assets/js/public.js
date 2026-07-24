(function() {
    'use strict';

    var buttons = document.querySelectorAll('.ssm-buttons');

    buttons.forEach(function(container) {
        var postId = container.getAttribute('data-post-id');
        var countEl = container.querySelector('.ssm-count');

        container.querySelectorAll('.ssm-share').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var href = btn.getAttribute('href');
                if (postId && typeof ssm_public !== 'undefined') {
                    var data = new FormData();
                    data.append('action', 'ssm_count');
                    data.append('post_id', postId);
                    data.append('nonce', ssm_public.nonce);
                    fetch(ssm_public.ajax_url, {
                        method: 'POST',
                        body: data,
                        credentials: 'same-origin'
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(json) {
                        if (json.success && countEl) {
                            countEl.textContent = json.data.count + ' shares';
                        }
                    })
                    .catch(function() {});
                }
                if (href && href !== '#') {
                    window.open(href, 'ssm-share', 'width=600,height=400');
                }
            });
        });

        container.querySelectorAll('.ssm-copy').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var url = container.getAttribute('data-url');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(url).then(function() {
                        var label = btn.querySelector('.ssm-label');
                        var original = label.textContent;
                        label.textContent = 'Copied!';
                        setTimeout(function() {
                            label.textContent = original;
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
    });
})();

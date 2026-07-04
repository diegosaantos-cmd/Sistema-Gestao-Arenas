<script>
    document.addEventListener('DOMContentLoaded', function () {
        const observers = new Map();

        async function carregarJson(url) {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Falha ao carregar clientes.');
            }

            return response.json();
        }

        document.querySelectorAll('[data-infinite-clients]').forEach(function (loader) {
            const scrollContainer = loader.closest('.table-responsive');
            const target = document.getElementById(loader.dataset.target);
            const spinner = loader.querySelector('[data-client-spinner]');
            let loading = false;

            const observer = new IntersectionObserver(async function (entries) {
                if (!entries[0].isIntersecting || loading || !loader.dataset.nextUrl) {
                    return;
                }

                const version = loader.dataset.version || '0';
                loading = true;
                spinner.classList.remove('d-none');

                try {
                    const data = await carregarJson(loader.dataset.nextUrl);

                    if ((loader.dataset.version || '0') !== version) {
                        return;
                    }

                    target.insertAdjacentHTML('beforeend', data.html);
                    loader.dataset.nextUrl = data.next_url || '';

                    if (!data.next_url) {
                        loader.classList.add('d-none');
                        observer.unobserve(loader);
                    }
                } catch (error) {
                    loader.innerHTML =
                        '<span class="small text-danger">Não foi possível carregar mais clientes.</span>';
                    observer.unobserve(loader);
                } finally {
                    loading = false;
                    spinner?.classList.add('d-none');
                }
            }, {
                root: scrollContainer,
                rootMargin: '800px 0px',
                threshold: 0
            });

            observers.set(loader.dataset.target, observer);

            if (loader.dataset.nextUrl) {
                observer.observe(loader);
            }
        });

        document.querySelectorAll('[data-client-search]').forEach(function (form) {
            const input = form.querySelector('input[name="busca_cliente"]');
            const targetId = form.dataset.target;
            const target = document.getElementById(targetId);
            const loader = document.querySelector(
                `[data-infinite-clients][data-target="${targetId}"]`
            );
            const observer = observers.get(targetId);
            let timer;
            let controller;

            form.addEventListener('submit', function (event) {
                event.preventDefault();
            });

            input.addEventListener('input', function () {
                clearTimeout(timer);

                timer = setTimeout(async function () {
                    controller?.abort();
                    controller = new AbortController();

                    const version = String(Number(loader.dataset.version || 0) + 1);
                    loader.dataset.version = version;
                    loader.dataset.nextUrl = '';
                    loader.classList.add('d-none');
                    observer.unobserve(loader);
                    target.style.opacity = '0.55';

                    const url = new URL(form.dataset.endpoint, window.location.origin);
                    const busca = input.value.trim();

                    if (busca) {
                        url.searchParams.set('busca_cliente', busca);
                    }

                    try {
                        const response = await fetch(url, {
                            signal: controller.signal,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Falha ao pesquisar clientes.');
                        }

                        const data = await response.json();
                        target.innerHTML = data.html;
                        loader.dataset.nextUrl = data.next_url || '';

                        if (data.next_url) {
                            loader.classList.remove('d-none');
                            observer.observe(loader);
                        }

                        loader.closest('.table-responsive').scrollTop = 0;
                    } catch (error) {
                        if (error.name !== 'AbortError') {
                            target.innerHTML =
                                '<tr><td colspan="7" class="text-center text-danger py-4">Não foi possível pesquisar.</td></tr>';
                        }
                    } finally {
                        target.style.opacity = '1';
                    }
                }, 180);
            });
        });
    });
</script>

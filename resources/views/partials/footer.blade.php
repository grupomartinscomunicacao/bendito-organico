<footer class="site-footer mt-auto">
    <div class="container">
        <div class="row g-4">

            {{-- Sempre visível: marca, resumo e canais diretos. --}}
            <div class="col-lg-4">
                <x-logo variant="light" class="brand-logo brand-logo--lg mb-3" />
                <p class="site-footer__about mb-3">{{ config('bendito.description') }}</p>

                <div class="site-footer__social d-flex gap-2">
                    @if ($instagram = config('bendito.social.instagram'))
                        <a href="{{ $instagram }}" class="social-link" target="_blank" rel="noopener" aria-label="Instagram">
                            <i class="bi bi-instagram" aria-hidden="true"></i>
                        </a>
                    @endif
                    @if ($facebook = config('bendito.social.facebook'))
                        <a href="{{ $facebook }}" class="social-link" target="_blank" rel="noopener" aria-label="Facebook">
                            <i class="bi bi-facebook" aria-hidden="true"></i>
                        </a>
                    @endif
                    @if ($whatsapp = config('bendito.contact.whatsapp'))
                        <a href="https://wa.me/{{ $whatsapp }}" class="social-link" target="_blank" rel="noopener" aria-label="WhatsApp">
                            <i class="bi bi-whatsapp" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>

                {{--
                    No celular o resto do rodapé é ruído: três colunas de links
                    entre o conteúdo e o fim da página. Ficam atrás de um
                    botão. O `.d-lg-block` vence o `display:none` do collapse
                    (é utilitário, vem com !important), então no desktop tudo
                    continua aberto e o botão nem existe.
                --}}
                <button
                    class="footer-toggle collapsed d-lg-none"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#rodapeDetalhes"
                    aria-expanded="false"
                    aria-controls="rodapeDetalhes"
                >
                    <span class="footer-toggle__more">Ver mais informações</span>
                    <span class="footer-toggle__less">Mostrar menos</span>
                    <i class="bi bi-chevron-down footer-toggle__chevron" aria-hidden="true"></i>
                </button>
            </div>

            <div class="col-lg-8 site-footer__details collapse d-lg-block" id="rodapeDetalhes">
                <div class="row g-4">
                    <div class="col-sm-6 col-lg-4">
                        <h5>Navegar</h5>
                        <ul class="list-unstyled d-grid gap-2 mb-0">
                            <li><a href="{{ route('home') }}">Início</a></li>
                            <li><a href="{{ route('products.index') }}">Produtos</a></li>
                            <li><a href="{{ route('about') }}">Sobre nós</a></li>
                            <li><a href="{{ route('contact') }}">Contato</a></li>
                            <li><a href="{{ route('orders.lookup') }}">Meu pedido</a></li>
                        </ul>
                    </div>

                    <div class="col-sm-6 col-lg-4">
                        <h5>Atendimento</h5>
                        <ul class="list-unstyled d-grid gap-2 mb-0">
                            <li><i class="bi bi-envelope me-2" aria-hidden="true"></i>{{ config('bendito.contact.email') }}</li>
                            <li><i class="bi bi-telephone me-2" aria-hidden="true"></i>{{ config('bendito.contact.phone') }}</li>
                            <li><i class="bi bi-geo-alt me-2" aria-hidden="true"></i>{{ config('bendito.contact.city') }}/{{ config('bendito.contact.state') }}</li>
                        </ul>
                    </div>

                    <div class="col-lg-4">
                        <h5>Entrega</h5>
                        <p class="mb-0" style="font-size: .9375rem">{{ config('bendito.checkout.delivery_notice') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-divider d-flex flex-wrap justify-content-between align-items-center gap-2 gap-md-3">
            <span>&copy; {{ date('Y') }} {{ config('bendito.name') }}. Todos os direitos reservados.</span>

            <span class="d-inline-flex align-items-center gap-2">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                Pagamento seguro via Mercado Pago
            </span>

            <span class="footer-credit">
                Desenvolvido por
                <a
                    href="{{ config('bendito.developer.url') }}"
                    target="_blank"
                    rel="noopener"
                >{{ config('bendito.developer.name') }}</a>
            </span>
        </div>
    </div>
</footer>

<!-- Core JS -->
<!-- build:js assets/vendor/js/core.js -->
<script src="{{ asset('System/assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('System/assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('System/assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('System/assets/vendor/libs/node-waves/node-waves.js') }}"></script>
<script src="{{ asset('System/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('System/assets/vendor/libs/hammer/hammer.js') }}"></script>
<script src="{{ asset('System/assets/vendor/js/menu.js') }}"></script>
<!-- endbuild -->

<script src="{{ asset('System/assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('System/assets/vendor/libs/bootstrap-select/bootstrap-select.js') }}"></script>

<!-- Vendors JS -->
<script src="{{ asset('System/assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
<script src="{{ asset('System/assets/js/ui-toasts.js') }}"></script>
<script src="{{ asset('System/assets/vendor/libs/toastr/toastr.js') }}"></script>
<script src="{{ asset('System/assets/vendor/libs/chartjs/chartjs.js') }}"></script>

<!-- Main JS -->
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" defer></script>
<script src="https://unpkg.com/html5-qrcode"></script>

<script src="{{ asset("System/assets/js/main.js") }}?v={{ filemtime(public_path("System/assets/js/main.js")) }}"></script>
<script src="{{ asset('System/assets/js/custom.js') }}"></script>
<!-- Page JS -->

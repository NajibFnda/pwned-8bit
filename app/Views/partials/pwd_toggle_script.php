<script>
    function togglePasswordField(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input || !btn) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        const openIcon = btn.querySelector('[data-eye="open"]');
        const closedIcon = btn.querySelector('[data-eye="closed"]');
        if (openIcon) openIcon.classList.toggle('hidden', show);
        if (closedIcon) closedIcon.classList.toggle('hidden', !show);
    }
</script>

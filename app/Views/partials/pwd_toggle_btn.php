<button type="button"
    onclick="togglePasswordField('<?= esc($target ?? '', 'js') ?>', this)"
    aria-label="<?= esc($label ?? 'Lihat atau sembunyikan sandi') ?>"
    aria-pressed="false"
    title="Lihat / Sembunyikan"
    class="px-3 bg-gray-100 hover:bg-black hover:text-white border-l-2 border-black flex items-center justify-center shrink-0 transition-none">
    <svg data-eye="open" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="square" stroke-linejoin="miter" aria-hidden="true">
        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
        <circle cx="12" cy="12" r="3"/>
    </svg>
    <svg data-eye="closed" class="w-4 h-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="square" stroke-linejoin="miter" aria-hidden="true">
        <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6.5 0-10-7-10-7a20.3 20.3 0 0 1 5.06-5.94"/>
        <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c6.5 0 10 7 10 7a20.5 20.5 0 0 1-3.16 4.19"/>
        <path d="M1 1l22 22"/>
    </svg>
</button>

<style>
    .tvri-footer {
        width: 100%;
        margin-top: auto;
        padding: 1.75rem 1.5rem;
        background-color: #ffffff;
        border-top: 1px solid #e5e7eb;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        font-size: 0.875rem;
        line-height: 1.5;
        color: #6b7280;
        box-sizing: border-box;
    }

    .tvri-footer-container {
        max-width: 1280px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.25rem;
        text-align: center;
    }

    .tvri-link {
        color: #2563eb;
        font-weight: 600;
        text-decoration: none;
    }

    .tvri-link:hover,
    .tvri-link:focus-visible {
        color: #1d4ed8;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    /* Bagian "Didesain & Dikembangkan oleh" */
    .tvri-credits {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }

    .tvri-credits-label {
        font-weight: 500;
        color: #374151;
    }

    /* Daftar nama: tiap nama jadi satu "chip" yang tidak terpotong */
    .tvri-credits-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.5rem;
    }

    .tvri-credits-list li {
        white-space: nowrap;
    }

    .tvri-credits-list .tvri-link {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        background-color: #f9fafb;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .tvri-credits-list .tvri-link:hover,
    .tvri-credits-list .tvri-link:focus-visible {
        background-color: #eff6ff;
        border-color: #bfdbfe;
        text-decoration: none;
    }

    /* Desktop lebar: semuanya sejajar dalam SATU baris, tanpa wrap */
    @media (min-width: 1100px) {
        .tvri-footer {
            padding: 1.5rem 2.5rem;
        }

        .tvri-footer-container {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
            text-align: left;
        }

        .tvri-copyright,
        .tvri-credits-label {
            white-space: nowrap;
        }

        .tvri-credits {
            flex-direction: row;
            align-items: center;
            flex-shrink: 0;
            gap: 0.75rem;
        }

        .tvri-credits-list {
            flex-wrap: nowrap;
            justify-content: flex-end;
        }
    }

    /* Layar sangat kecil: nama ditumpuk rapi */
    @media (max-width: 480px) {
        .tvri-footer {
            font-size: 0.8125rem;
        }

        .tvri-credits-list {
            flex-direction: column;
            align-items: center;
        }
    }

    /* Dark mode */
    .fi-theme-dark .tvri-footer,
    html.dark .tvri-footer {
        background-color: #18181b;
        border-top-color: #27272a;
        color: #a1a1aa;
    }

    .fi-theme-dark .tvri-credits-label,
    html.dark .tvri-credits-label {
        color: #d4d4d8;
    }

    .fi-theme-dark .tvri-link,
    html.dark .tvri-link {
        color: #60a5fa;
    }

    .fi-theme-dark .tvri-link:hover,
    .fi-theme-dark .tvri-link:focus-visible,
    html.dark .tvri-link:hover,
    html.dark .tvri-link:focus-visible {
        color: #93c5fd;
    }

    .fi-theme-dark .tvri-credits-list .tvri-link,
    html.dark .tvri-credits-list .tvri-link {
        background-color: #27272a;
        border-color: #3f3f46;
    }

    .fi-theme-dark .tvri-credits-list .tvri-link:hover,
    .fi-theme-dark .tvri-credits-list .tvri-link:focus-visible,
    html.dark .tvri-credits-list .tvri-link:hover,
    html.dark .tvri-credits-list .tvri-link:focus-visible {
        background-color: #1e3a5f;
        border-color: #1d4ed8;
    }

    @media print {
        .tvri-footer {
            border-top: 1px solid #000;
            background-color: #fff !important;
        }
    }
</style>

<footer class="tvri-footer">
    <div class="tvri-footer-container">
        <div class="tvri-copyright">
            Copyright {{ date('Y') }}
            <a href="https://yogyakarta.tvri.go.id/" target="_blank" rel="noopener noreferrer" class="tvri-link">
                TVRI Stasiun D.I. Yogyakarta
            </a>
        </div>

        <div class="tvri-credits">
            <span class="tvri-credits-label">&lt;/&gt; Didesain &amp; Dikembangkan oleh</span>
            <ul class="tvri-credits-list">
                <li>
                    <a href="https://www.instagram.com/leoarethere/" target="_blank" rel="noopener noreferrer" class="tvri-link">
                        Leonardo Putra Susanto
                    </a>
                </li>
                <li>
                    <a href="https://www.instagram.com/destywahyu01/" target="_blank" rel="noopener noreferrer" class="tvri-link">
                        Desty Wahyu Anjani
                    </a>
                </li>
                <li>
                    <a href="#" class="tvri-link">
                        Ilham Bagaskara
                    </a>
                </li>
            </ul>
        </div>
    </div>
</footer>
<style>
    .tvri-footer {
        width: 100%;
        padding: 2rem 1.5rem;
        background-color: #ffffff;
        border-top: 1px solid #e5e7eb;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        box-sizing: border-box;
        margin-top: auto;
    }
    
    .tvri-footer-container {
        max-width: 1280px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        align-items: center;
        justify-content: center;
    }

    .tvri-text {
        font-size: 0.875rem;
        color: #6b7280;
        line-height: 1.5;
        text-align: center;
    }

    .tvri-link {
        color: #2563eb;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s ease;
        display: inline-block;
    }
    
    .tvri-link:hover {
        text-decoration: underline;
        color: #1d4ed8;
        transform: translateY(-1px);
    }

    .tvri-copyright {
        text-align: center;
    }

    .tvri-credits {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        text-align: center;
    }

    .tvri-credits-label {
        font-weight: 500;
        color: #374151;
    }

    .tvri-separator {
        color: #9ca3af;
        margin: 0 0.25rem;
    }

    /* Responsif untuk Tablet */
    @media (min-width: 768px) {
        .tvri-footer {
            padding: 2.5rem 2rem;
        }
        
        .tvri-footer-container {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .tvri-copyright,
        .tvri-credits {
            text-align: left;
        }
    }

    /* Responsif untuk Desktop */
    @media (min-width: 1024px) {
        .tvri-footer {
            padding: 2rem 2.5rem;
        }
    }

    /* Mobile - Layar sangat kecil */
    @media (max-width: 480px) {
        .tvri-text {
            font-size: 0.8125rem;
        }
        
        .tvri-credits {
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .tvri-separator {
            display: none;
        }
    }

    /* Dark Mode Support */
    .fi-theme-dark .tvri-footer, 
    html.dark .tvri-footer {
        background-color: #18181b;
        border-top-color: #27272a;
    }
    
    .fi-theme-dark .tvri-text,
    html.dark .tvri-text {
        color: #a1a1aa;
    }
    
    .fi-theme-dark .tvri-credits-label,
    html.dark .tvri-credits-label {
        color: #d4d4d8;
    }
    
    .fi-theme-dark .tvri-separator,
    html.dark .tvri-separator {
        color: #52525b;
    }
    
    .fi-theme-dark .tvri-link,
    html.dark .tvri-link {
        color: #60a5fa;
    }
    
    .fi-theme-dark .tvri-link:hover,
    html.dark .tvri-link:hover {
        color: #93c5fd;
    }

    /* Print Style */
    @media print {
        .tvri-footer {
            border-top: 1px solid #000;
            background-color: #fff !important;
        }
    }
</style>

<footer class="tvri-footer">
    <div class="tvri-footer-container">
        <!-- Copyright Section -->
        <div class="tvri-copyright tvri-text">
            Copyright {{ date('Y') }} 
            <a href="https://yogyakarta.tvri.go.id/" target="_blank" rel="noopener noreferrer" class="tvri-link">
                TVRI Stasiun D.I. Yogyakarta
            </a>
        </div>

        <!-- Credits Section -->
        <div class="tvri-credits tvri-text">
            <span class="tvri-credits-label">&lt;/&gt; Didesain & Dikembangkan oleh:</span>
            <a href="https://www.instagram.com/leoarethere/" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="tvri-link">
                Leonardo Putra Susanto
            </a>
            <span class="tvri-separator">&amp;</span>
            <a href="https://www.instagram.com/destywahyu01/" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="tvri-link">
                Desty Wahyu Anjani
            </a>
        </div>
    </div>
</footer>
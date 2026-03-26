setTimeout(() => {
    const container = document.getElementById('homepage-items');
    if (!container) return; // Execute only on homepage

    // Inject custom CSS
    const style = document.createElement('style');
    style.innerHTML = `
        .komga-card-hover:hover { transform: scale(1.05) !important; }
        .komga-container::-webkit-scrollbar { display: none; }
        .komga-container { scrollbar-width: none; }
    `;
    document.head.appendChild(style);

    const TAB_NAME = 'Komga-(Livres)';

    async function loadLatestBooks() {
        try {
            // Hit our secure local API
            const response = await fetch('api/v2/plugins/komga/books/latest', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            const res = await response.json();
            
            if (response.ok && res.response?.data) {
                const data = res.response.data;
                const books = Array.isArray(data.books) ? data.books : [];
                if (books.length > 0) {
                    renderBooks(books, data.title, data.baseUrl);
                }
            }
        } catch (e) {
            console.error('Erreur Komga API:', e);
        }
    }

    window.komgaFetchAuthImage = async function(url, elementId) {
        try {
            const proxyUrl = `api/v2/plugins/komga/image?url=${encodeURIComponent(url)}`;
            const res = await fetch(proxyUrl);
            if (!res.ok) throw new Error();
            const blob = await res.blob();
            const objectURL = URL.createObjectURL(blob);
            const el = document.getElementById(elementId);
            if (el) el.style.backgroundImage = `url('${objectURL}')`;
        } catch (err) {
            console.error("Erreur chargement image sécurisée", err);
        }
    }

    window.komgaOpenBook = function(komgaBaseUrl, bookId) {
        const targetUrl = `${komgaBaseUrl}${bookId}`;
        const sideBarLinks = document.querySelectorAll('.sidebar-nav a, .tab-menu-item, .nav-link');
        let found = false;
        sideBarLinks.forEach(link => {
            if (link.textContent.includes('Komga')) {
                link.click();
                found = true;
            }
        });
        if (!found) window.location.hash = `#${TAB_NAME}`;
        
        setTimeout(() => {
            const iframe = document.querySelector('iframe[src*="komga"]');
            if (iframe) iframe.src = targetUrl;
        }, 700);
    };

    function renderBooks(books, titleDisplay, komgaBaseUrl) {
        const container = document.getElementById('homepage-items');
        if (!container) return;

        let html = `
        <div class="row" style="margin-bottom: 30px; margin-top: 20px;">
            <div class="col-lg-12">
                <div style="display: flex; align-items: center; margin-bottom: 15px; padding-left: 5px;">
                    <img src="api/plugins/komga/logo.svg" style="width: 22px; height: 22px; margin-right: 10px;" onerror="this.style.display='none'">
                    <span style="text-transform: uppercase; font-weight: bold; font-size: 13px; letter-spacing: 1px; color: #eee;">${titleDisplay}</span>
                </div>
                <div class="komga-container" style="display: flex; flex-wrap: nowrap; overflow-x: auto; gap: 12px; padding: 5px;">`;

        books.forEach((book, index) => {
            const imgId = `komga-img-${index}`;
            const title = book.metadata?.title || book.name || 'Sans titre';
            const series = book.seriesTitle || '';

            html += `
                <div style="flex: 0 0 150px; max-width: 150px; cursor: pointer;" onclick="komgaOpenBook('${komgaBaseUrl}', '${book.id}')">
                    <div id="${imgId}" class="komga-card-hover" 
                         style="background-color: #333; background-size: cover; background-position: center; width: 100%; aspect-ratio: 2/3; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.5); margin-bottom: 8px; transition: transform 0.2s ease;">
                    </div>
                    <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <span style="font-size: 12px; font-weight: 500; color: #fff; display: block;">${title}</span>
                        <span style="font-size: 11px; color: #aaa; display: block;">${series}</span>
                    </div>
                </div>`;
            
            const thumbUrl = book.thumbnailUrl || `/api/v1/books/${book.id}/thumbnail`;
            window.komgaFetchAuthImage(thumbUrl, imgId);
        });

        html += `</div></div></div>`;
        
        const temp = document.createElement('div');
        temp.innerHTML = html;
        if (container.children.length >= 4) {
            container.insertBefore(temp.firstElementChild, container.children[4]);
        } else {
            container.appendChild(temp.firstElementChild);
        }
    }

    loadLatestBooks();
}, 800);

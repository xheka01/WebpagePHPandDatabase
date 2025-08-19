<?php
session_start();
require 'config.php';

// --- BÚSQUEDA SERVER-SIDE (fallback si no hay JS) ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
$params = [];

if ($search !== '') {
    // Usamos la misma lógica de boolean query simple para SSR
    $terms = preg_split('/\s+/', $search);
    $booleanQuery = [];
    foreach ($terms as $t) {
        $t = preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $t);
        $t = trim($t);
        if ($t !== '') $booleanQuery[] = '+' . $t . '*';
    }
    if (!empty($booleanQuery)) {
        $where = "WHERE MATCH(name, description, search_tags) AGAINST (? IN BOOLEAN MODE)";
        $params[] = implode(' ', $booleanQuery);
    }
}

// Consulta inicial para render SSR
$stmt = $pdo->prepare("SELECT id, name, description, price, image_path FROM products $where ORDER BY id DESC LIMIT 50");
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BW</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/navbar.css">
    <script>
        function toggleSettings() {
            const settingsMenu = document.getElementById('settingsMenu');
            settingsMenu.style.display = (settingsMenu.style.display === 'none' || !settingsMenu.style.display) ? 'block' : 'none';
        }
        function confirmDelete() {
            const confirmation = prompt("Type CONFIRM to delete your account:");
            if (confirmation === "CONFIRM") {
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</head>
<body data-auth="<?php echo isset($_SESSION['user_id']) ? '1' : '0'; ?>">

    <?php require __DIR__ . '/navbar.php'; ?>

    <div class="hero">
        <h1>Welcome to BW, Birlanga's Wear</h1>
        <p>Discover the latest trends in fashion with our exclusive collection</p>
    </div>

    <div class="product-section">
        <h2 class="section-title">Our Products</h2>
        <div class="product-grid">
            <?php foreach($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if(!empty($product['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="max-width: 65%; height: auto;">
                        <?php else: ?>
                            <img src="/api/placeholder/250/200"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="max-width: 65%; height: auto;">
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                    <div class="price">$<?php echo number_format((float)$product['price'], 2); ?></div>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <form action="add_to_cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                            <button type="submit" class="add-to-cart">Add to Cart</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- JS de búsqueda AJAX (con fallback SSR) -->
    <script>
    (function(){
        function escapeHtml(str) {
            return (str ?? '').toString().replace(/[&<>"'`=\/]/g, s => ({
                '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#96;','=':'&#61;','/':'&#47;'
            }[s]));
        }
        function debounce(fn, delay=300) {
            let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), delay); };
        }

        const form = document.querySelector('.search-form');
        const input = form ? form.querySelector('input[name="search"]') : null;
        const grid = document.querySelector('.product-grid');
        const isLogged = document.body.getAttribute('data-auth') === '1';

        if (!form || !input || !grid) return;

        const loaderId = 'ajax-loader';
        function setLoading(loading) {
            let loader = document.getElementById(loaderId);
            if (loading) {
                if (!loader) {
                    loader = document.createElement('div');
                    loader.id = loaderId;
                    loader.textContent = 'Buscando...';
                    loader.style.margin = '10px 0';
                    form.insertAdjacentElement('afterend', loader);
                }
            } else if (loader) loader.remove();
        }

        function renderProducts(products){
            if (!Array.isArray(products) || products.length === 0) {
                grid.innerHTML = '<div class="no-results">No hay resultados.</div>';
                return;
            }
            grid.innerHTML = products.map(p=>{
                const img = p.image_path && p.image_path.trim() !== ''
                    ? `<img src="${escapeHtml(p.image_path)}" alt="${escapeHtml(p.name)}" style="max-width:65%;height:auto;">`
                    : `<img src="/api/placeholder/250/200" alt="${escapeHtml(p.name)}" style="max-width:65%;height:auto;">`;
                const cart = isLogged ? `
                    <form action="add_to_cart.php" method="POST">
                        <input type="hidden" name="product_id" value="${String(p.id)}">
                        <button type="submit" class="add-to-cart">Add to Cart</button>
                    </form>` : '';
                return `
                    <div class="product-card">
                        <div class="product-image">${img}</div>
                        <h3>${escapeHtml(p.name)}</h3>
                        <p>${escapeHtml(p.description || '')}</p>
                        <div class="price">$${Number(p.price).toFixed(2)}</div>
                        ${cart}
                    </div>`;
            }).join('');
        }

        async function doSearch(q){
            try{
                setLoading(true);
                console.log("Buscando productos con:", q);
                // Esto es ajax
                const res = await fetch('search_api.php?q=' + encodeURIComponent(q || ''), {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                }); 
                // Verifica si la respuesta es exitosa
                const data = await res.json();
                if (!res.ok || !data.ok) throw new Error(data.message || 'Error en la búsqueda');
                renderProducts(data.products);
            } catch(err){
                console.error(err);
                grid.innerHTML = '<div class="error">Ha ocurrido un error al buscar.</div>';
            } finally {
                setLoading(false);
            }
        }

        // Intercepta submit (sin recarga de página)
        form.addEventListener('submit', (e)=>{
            e.preventDefault();
            doSearch(input.value);
            // Actualiza la URL para permitir compartir/enlazar
            const url = new URL(location.href);
            if (input.value) url.searchParams.set('search', input.value);
            else url.searchParams.delete('search');
            history.replaceState({}, '', url);
        });

        // Búsqueda en vivo
        input.addEventListener('input', debounce(()=>{
            doSearch(input.value);
            const url = new URL(location.href);
            if (input.value) url.searchParams.set('search', input.value);
            else url.searchParams.delete('search');
            history.replaceState({}, '', url);
        }, 350));
    })();
    </script>
</body>
</html>

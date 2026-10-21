import React from 'react';
import { router, usePage } from '@inertiajs/react';

const money = new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
    minimumFractionDigits: 2,
});

const categoryNames = {
    pez: 'Peces', invertebrado: 'Invertebrados', coral: 'Corales', acuario: 'Acuarios y tanques',
    filtracion: 'Filtros y filtración', sustrato: 'Arena y sustratos', decoracion: 'Piedras y decoración',
    alimento: 'Alimento', medicina: 'Salud y diagnóstico', accesorio: 'Equipo y accesorios',
};

const categoryImages = {
    acuario: '/images/cat-aquariums.jpg', filtracion: '/images/cat-equipment.jpg',
    sustrato: '/images/cat-aquariums.jpg', decoracion: '/images/cat-aquariums.jpg',
    alimento: '/images/cat-food.jpg', medicina: '/images/cat-equipment.jpg',
    accesorio: '/images/cat-equipment.jpg',
};

function ProductImage({ product }) {
    const fallbackImage = categoryImages[product.category] || (product.water_type === 'salada' ? '/images/hero-reef.jpg' : '/images/cat-aquariums.jpg');
    const image = product.image_src || product.image_path || fallbackImage;
    const isSpeciesPhoto = product.category === 'pez' || Boolean(product.image_path?.includes('/images/fish-'));
    return (
        <div className="relative aspect-[1.35] overflow-hidden bg-slate-100">
            <img
                src={image}
                alt={isSpeciesPhoto ? product.name : `Imagen ilustrativa de ${categoryNames[product.category] || 'acuario'}`}
                loading="lazy"
                onError={(event) => {
                    event.currentTarget.onerror = null;
                    event.currentTarget.src = fallbackImage;
                }}
                className="h-full w-full object-cover transition duration-300 hover:scale-[1.03]"
            />
            {product.image_credit ? (
                <a href={product.image_credit.source} target="_blank" rel="noreferrer" title={`${product.image_credit.author || product.image_credit.commonsFile} · ${product.image_credit.license}`} className="absolute bottom-2 left-2 max-w-[calc(100%-1rem)] truncate rounded-full bg-[#0b192c]/90 px-2.5 py-1 text-[10px] font-semibold text-white underline decoration-white/50 underline-offset-2">
                    Foto: {product.image_credit.author || product.image_credit.commonsFile} · {product.image_credit.license}
                </a>
            ) : !isSpeciesPhoto && <span className="absolute bottom-2 left-2 rounded-full bg-[#0b192c]/80 px-2.5 py-1 text-[10px] font-semibold text-white">Imagen ilustrativa</span>}
        </div>
    );
}

function ProductCard({ product }) {
    const availableStock = Number(product.available_stock ?? product.stock);

    function addToCart() {
        const isLive = ['pez', 'invertebrado', 'coral'].includes(product.category);
        const accepted = !isLive || window.confirm(`Carta de cuidado responsable\n\n${product.name} es un ejemplar vivo. Confirma que revisarás sus cuidados, parámetros de agua y necesidades antes de integrarlo a un acuario. Esta aceptación quedará registrada para el pedido.\n\n¿Aceptas continuar?`);
        if (!accepted) return;
        router.post('/carrito/items', { product_id: product.id, quantity: 1, care_responsibility_accepted: isLive }, { preserveScroll: true });
    }
    return (
        <article className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-900/10">
            <ProductImage product={product} />
            <div className="p-4">
                <p className="text-xs font-semibold uppercase tracking-wide text-teal-700">
                    {product.water_type === 'salada' ? 'Agua salada' : product.water_type === 'dulce' ? 'Agua dulce' : categoryNames[product.category] || product.category}
                </p>
                <h2 className="mt-1 text-base font-bold text-[#0b192c]">{product.name}</h2>
                {product.scientific_name && <p className="mt-0.5 text-xs italic text-slate-500">{product.scientific_name}</p>}
                <p className="mt-2 line-clamp-2 min-h-10 text-sm text-slate-600">{product.description || 'Consulta la ficha técnica en tienda.'}</p>
                <div className="mt-4 flex items-center justify-between gap-3">
                    <span className="font-extrabold text-[#0b192c]">{product.category === 'medicina' && Number(product.price) === 0 ? 'Pendiente de validación' : money.format(Number(product.price))}</span>
                    {availableStock > 0 ? <button type="button" onClick={addToCart} className="rounded-lg bg-[#08c2cd] px-3 py-2 text-xs font-extrabold text-[#0b192c] hover:bg-[#20d5dc]">Agregar</button> : <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{product.category === 'medicina' ? 'No disponible' : 'Agotado'}</span>}
                </div>
                <p className={`mt-2 text-xs font-semibold ${availableStock > 5 ? 'text-emerald-700' : availableStock > 0 ? 'text-amber-700' : 'text-slate-500'}`}>
                    {availableStock} {availableStock === 1 ? 'pieza disponible' : 'piezas disponibles'}
                </p>
                {product.water_type && (
                    <dl className="mt-4 grid grid-cols-2 gap-2 border-t border-slate-100 pt-3 text-xs text-slate-600">
                        {product.min_ph && <div><dt className="font-semibold text-slate-800">pH</dt><dd>{product.min_ph}–{product.max_ph}</dd></div>}
                        {product.min_temp_c && <div><dt className="font-semibold text-slate-800">Temperatura</dt><dd>{product.min_temp_c}–{product.max_temp_c} °C</dd></div>}
                    </dl>
                )}
                {product.minimum_tank_liters && (
                    <details className="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-600">
                        <summary className="cursor-pointer font-bold text-teal-800">Cuidados orientativos</summary>
                        <dl className="mt-3 grid grid-cols-2 gap-2">
                            {product.adult_size_cm && <div><dt className="font-semibold text-slate-800">Tamaño adulto</dt><dd>Hasta {product.adult_size_cm} cm</dd></div>}
                            <div><dt className="font-semibold text-slate-800">Volumen recomendado</dt><dd>Desde {product.minimum_tank_liters} L</dd></div>
                            {product.minimum_group_size && <div><dt className="font-semibold text-slate-800">Grupo mínimo</dt><dd>{product.minimum_group_size === 1 ? 'Individual' : `${product.minimum_group_size} ejemplares`}</dd></div>}
                            {product.care_level && <div><dt className="font-semibold text-slate-800">Cuidado</dt><dd className="capitalize">{product.care_level}</dd></div>}
                        </dl>
                        {product.care_notes && <p className="mt-3 rounded-lg bg-slate-50 p-2.5 leading-5">{product.care_notes}</p>}
                    </details>
                )}
            </div>
        </article>
    );
}

export default function CatalogIndex({ products, filters, brands }) {
    const { props } = usePage();
    function applyFilters(event) {
        event.preventDefault();
        const values = Object.fromEntries(new FormData(event.currentTarget).entries());
        const query = Object.fromEntries(Object.entries(values).filter(([, value]) => value !== ''));

        router.get('/catalogo', query, {
            preserveScroll: true,
            replace: true,
            only: ['products', 'filters', 'brands'],
        });
    }

    return (
        <div className="min-h-screen bg-slate-50 text-[#0b192c]">
            <header className="bg-[#0b192c] text-white">
                <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4">
                    <a href="/" className="text-lg font-black tracking-tight" aria-label="AQUARIUM, inicio">AQUARIUM</a>
                    <nav className="flex items-center gap-5 text-sm" aria-label="Navegación principal">
                        <a href="/" className="text-slate-300 hover:text-white">Inicio</a>
                        <a href="/catalogo" className="font-bold text-[#08c2cd]">Catálogo</a>
                        <a href="/#clinica" className="text-slate-300 hover:text-white">Clínica</a>
                        <a href="/#informacion" className="text-slate-300 hover:text-white">Información</a>
                        <a href="/carrito" className="text-slate-300 hover:text-[#08c2cd]">Carrito</a>
                    </nav>
                </div>
            </header>

            <main className="mx-auto max-w-7xl px-5 py-8 sm:py-12">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.14em] text-teal-700">AQUARIUM · Catálogo público</p>
                        <h1 className="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Peces e insumos</h1>
                        <p className="mt-2 text-sm text-slate-600">Explora el catálogo sin necesidad de iniciar sesión.</p>
                        <p className="mt-2 max-w-3xl text-xs leading-5 text-slate-500">Los parámetros y volúmenes son orientativos; confirma compatibilidad y condiciones del acuario antes de elegir. Precios y existencias de esta versión local son datos de demostración.</p>
                        {props.errors?.care && <div role="alert" className="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{props.errors.care}</div>}
                    </div>
                    <a href="/" className="text-sm font-semibold text-teal-800 hover:underline">Volver al inicio</a>
                </div>

                <form key={JSON.stringify(filters)} action="/catalogo" method="get" onSubmit={applyFilters} className="mt-7 grid gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-900/10 sm:grid-cols-2 lg:grid-cols-8">
                    <label className="sm:col-span-2 lg:col-span-2">
                        <span className="mb-1 block text-xs font-bold text-slate-700">Buscar</span>
                        <input name="q" defaultValue={filters.q || ''} placeholder="Nombre, descripción, SKU o marca" className="w-full rounded-lg border-2 border-[#08aeb8] bg-white text-sm shadow-[0_2px_8px_rgba(8,174,184,0.10)] focus:border-teal-700 focus:ring-4 focus:ring-teal-100" />
                    </label>
                    <label>
                        <span className="mb-1 block text-xs font-bold text-slate-700">Tipo de agua</span>
                        <select name="water_type" defaultValue={filters.water_type || ''} className="w-full rounded-lg border-2 border-[#3974b8] bg-white text-sm shadow-[0_2px_8px_rgba(57,116,184,0.10)] focus:border-blue-700 focus:ring-4 focus:ring-blue-100">
                            <option value="">Todos</option>
                            <option value="dulce">Dulce</option>
                            <option value="salada">Salada</option>
                        </select>
                    </label>
                    <label>
                        <span className="mb-1 block text-xs font-bold text-slate-700">Categoría</span>
                        <select name="category" defaultValue={filters.category || ''} className="w-full rounded-lg border-2 border-[#087d87] bg-white text-sm shadow-[0_2px_8px_rgba(8,125,135,0.10)] focus:border-teal-800 focus:ring-4 focus:ring-teal-100">
                            <option value="">Todas</option>
                            {Object.entries(categoryNames).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                        </select>
                    </label>
                    <label>
                        <span className="mb-1 block text-xs font-bold text-slate-700">Marca</span>
                        <select name="brand" defaultValue={filters.brand || ''} className="w-full rounded-lg border-2 border-[#8062a8] bg-white text-sm shadow-[0_2px_8px_rgba(128,98,168,0.10)] focus:border-purple-700 focus:ring-4 focus:ring-purple-100">
                            <option value="">Todas</option>
                            {brands.map((brand) => <option key={brand} value={brand}>{brand}</option>)}
                        </select>
                    </label>
                    <label>
                        <span className="mb-1 block text-xs font-bold text-slate-700">Precio mínimo</span>
                        <input name="min_price" type="number" min="0" step="0.01" defaultValue={filters.min_price || ''} className="w-full rounded-lg border-2 border-[#c58a1b] bg-white text-sm shadow-[0_2px_8px_rgba(197,138,27,0.10)] focus:border-amber-700 focus:ring-4 focus:ring-amber-100" />
                    </label>
                    <label>
                        <span className="mb-1 block text-xs font-bold text-slate-700">Precio máximo</span>
                        <input name="max_price" type="number" min="0" step="0.01" defaultValue={filters.max_price || ''} className="w-full rounded-lg border-2 border-[#b25c70] bg-white text-sm shadow-[0_2px_8px_rgba(178,92,112,0.10)] focus:border-rose-700 focus:ring-4 focus:ring-rose-100" />
                    </label>
                    <div className="flex items-end gap-2">
                        <button type="submit" className="w-full rounded-lg bg-[#0b192c] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#173555]">Aplicar</button>
                        <a href="/catalogo" className="rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Limpiar</a>
                    </div>
                </form>

                <div className="mt-6 flex items-center justify-between text-sm text-slate-600">
                    <p>{products.total} productos encontrados</p>
                    <p>Página {products.current_page} de {products.last_page}</p>
                </div>

                {products.data.length ? (
                    <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {products.data.map((product) => <ProductCard key={product.id} product={product} />)}
                    </div>
                ) : (
                    <div className="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center">
                        <h2 className="font-bold">No encontramos productos con esos filtros</h2>
                        <p className="mt-1 text-sm text-slate-600">Cambia la búsqueda o limpia los filtros para ver el catálogo.</p>
                    </div>
                )}

                {products.links?.length > 3 && (
                    <nav className="mt-8 flex flex-wrap justify-center gap-2" aria-label="Paginación del catálogo">
                        {products.links.map((link, index) => (
                            <a key={index} href={link.url || '#'} aria-disabled={!link.url} dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`rounded-lg border px-3 py-2 text-sm ${link.active ? 'border-[#0b192c] bg-[#0b192c] text-white' : 'border-slate-300 bg-white text-slate-700'} ${!link.url ? 'pointer-events-none opacity-40' : 'hover:border-teal-600'}`} />
                        ))}
                    </nav>
                )}
            </main>

            <footer className="mt-12 bg-[#0b192c] px-5 py-6 text-center text-sm text-slate-400">
                AQUARIUM · Catálogo disponible para consulta pública
            </footer>
        </div>
    );
}

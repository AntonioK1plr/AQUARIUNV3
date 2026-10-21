import React, { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';

const categories = [
    { id: 'dulce', title: 'Peces Agua Dulce', image: '/images/cat-freshwater.jpg' },
    { id: 'salada', title: 'Peces Agua Salada', image: '/images/cat-saltwater.jpg' },
    { id: 'acuario', title: 'Peceras y Acuarios', image: '/images/cat-aquariums.jpg' },
    { id: 'alimento', title: 'Alimento Premium', image: '/images/cat-food.jpg' },
    { id: 'filtracion', title: 'Filtros y Filtración', image: '/images/cat-equipment.jpg' },
    { id: 'sustrato', title: 'Arena y Sustratos', image: '/images/cat-aquariums.jpg' },
    { id: 'decoracion', title: 'Piedras y Decoración', image: '/images/cat-aquariums.jpg' },
    { id: 'medicina', title: 'Salud y Diagnóstico', image: '/images/cat-equipment.jpg' },
    { id: 'accesorio', title: 'Equipo y Accesorios', image: '/images/cat-equipment.jpg' },
];

const money = new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
    maximumFractionDigits: 0,
});

function FishMark() {
    return (
        <span className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#08c2cd] text-[#0b192c]">
            <svg viewBox="0 0 32 32" aria-hidden="true" className="h-6 w-6 fill-none stroke-current stroke-[1.8]">
                <path d="M4 16c4.5-6.2 13.1-7.5 19.5-1.8L28 11v10l-4.5-3.2C17.1 23.5 8.5 22.2 4 16Z" />
                <circle cx="12" cy="14" r="1" className="fill-current" />
                <path d="M17 11.2c-2.6 3.1-2.6 6.5 0 9.6" />
            </svg>
        </span>
    );
}

function PinIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className="h-4 w-4 fill-none stroke-current stroke-2">
            <path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z" />
            <circle cx="12" cy="10" r="2" />
        </svg>
    );
}

function SearchIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className="h-5 w-5 fill-none stroke-current stroke-[2]">
            <circle cx="10.8" cy="10.8" r="6.7" />
            <path d="m16 16 4.2 4.2" />
        </svg>
    );
}

function BagIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className="h-5 w-5 fill-none stroke-current stroke-[1.8]">
            <path d="M5 8h14l-1 12H6L5 8Z" />
            <path d="M9 9V6a3 3 0 0 1 6 0v3" />
        </svg>
    );
}

function ProductCard({ product, quantity, onAdd }) {
    return (
        <article className="group overflow-hidden rounded-2xl bg-[#f2f6f8] shadow-sm ring-1 ring-slate-900/[0.04] transition hover:-translate-y-1 hover:shadow-lg">
            <div className="relative aspect-[1.37] overflow-hidden bg-slate-200">
                <img
                    src={product.image}
                    alt={product.alt}
                    onError={(event) => {
                        event.currentTarget.onerror = null;
                        event.currentTarget.src = '/images/hero-reef.jpg';
                    }}
                    className="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                    loading="lazy"
                />
                <span className="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-[#147f89] shadow-sm">
                    Origen responsable
                </span>
            </div>
            <div className="p-4">
                <p className="text-xs font-medium text-slate-500">{product.family}</p>
                <h3 className="mt-1 min-h-11 text-sm font-bold leading-5 text-[#0b192c] sm:text-base">
                    {product.title}
                </h3>
                <div className="mt-3 flex items-center justify-between gap-3">
                    <span className="text-lg font-extrabold tracking-tight text-[#0b192c]">
                        {money.format(product.price)}
                    </span>
                    <button
                        type="button"
                        onClick={() => onAdd(product)}
                        aria-label={`Agregar ${product.title} al carrito`}
                        className={`grid h-9 w-9 place-items-center rounded-xl font-bold transition ${quantity ? 'bg-[#0b192c] text-white' : 'bg-[#08c2cd] text-[#0b192c] hover:bg-[#10d5df]'}`}
                    >
                        {quantity ? quantity : '+'}
                    </button>
                </div>
            </div>
        </article>
    );
}

export default function Home({ featuredProducts = [], auth = { user: null }, cart: savedCart = { itemsCount: 0 } }) {
    const [query, setQuery] = useState('');
    const [activeCategory, setActiveCategory] = useState('');
    const [cart, setCart] = useState({});
    const [notice, setNotice] = useState('');
    const [menuOpen, setMenuOpen] = useState(false);
    const cartCount = Number(savedCart?.itemsCount || 0) + Object.values(cart).reduce((total, count) => total + count, 0);

    const products = useMemo(() => featuredProducts.map((product) => ({
        id: product.sku,
        productId: product.id,
        title: product.name,
        family: product.water_type === 'salada' ? 'Fauna marina · Agua salada' : 'Fauna tropical · Agua dulce',
        category: product.water_type,
        price: Number(product.price),
        image: product.image_path,
        alt: product.description || product.name,
    })), [featuredProducts]);

    const visibleProducts = useMemo(() => {
        const normalized = query.trim().toLocaleLowerCase('es-MX');
        return products.filter((product) => {
            const matchesCategory = !activeCategory || product.category === activeCategory;
            const matchesQuery = !normalized || `${product.title} ${product.family}`.toLocaleLowerCase('es-MX').includes(normalized);
            return matchesCategory && matchesQuery;
        });
    }, [activeCategory, query, products]);

    function addToCart(product) {
        setCart((current) => ({ ...current, [product.id]: (current[product.id] ?? 0) + 1 }));
        setNotice(`${product.title} se agregó al carrito`);
        router.post('/carrito/items', { product_id: product.productId, quantity: 1 }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => setCart({}),
        });
        window.setTimeout(() => setNotice(''), 2600);
    }

    function chooseCategory(categoryId) {
        const filter = ['dulce', 'salada'].includes(categoryId)
            ? `water_type=${categoryId}`
            : `category=${categoryId}`;
        window.location.assign(`/catalogo?${filter}`);
    }

    function searchProducts(event) {
        event.preventDefault();
        const search = new URLSearchParams();
        if (query.trim()) search.set('q', query.trim());
        window.location.assign(`/catalogo?${search.toString()}`);
    }

    const navLinks = [
        ['Inicio', '#inicio'],
        ['Catálogo', '/catalogo'],
        ['Clínica', '#clinica'],
        ['Información', '#informacion'],
    ];

    return (
        <div className="min-h-screen bg-white text-[#0b192c]">
            <header className="sticky top-0 z-40 border-b border-white/10 bg-[#0b192c] text-white shadow-lg shadow-slate-950/10">
                <div className="mx-auto flex h-[72px] max-w-[1440px] items-center justify-between gap-5 px-5 sm:px-8">
                    <a href="#inicio" className="flex shrink-0 items-center gap-2.5" aria-label="AQUARIUM, inicio">
                        <FishMark />
                        <span className="text-lg font-black tracking-tight sm:text-xl">AQUARIUM</span>
                        <span className="hidden rounded bg-[#08c2cd]/15 px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-[#08c2cd] sm:inline-flex">Responsable</span>
                    </a>

                    <nav className="hidden items-center gap-7 lg:flex" aria-label="Navegación principal">
                        {navLinks.map(([label, href], index) => (
                            <a
                                key={label}
                                href={href}
                                className={`relative py-2 text-sm transition hover:text-[#08c2cd] ${index === 0 ? 'font-bold text-[#08c2cd] after:absolute after:inset-x-0 after:-bottom-1 after:mx-auto after:h-0.5 after:w-7 after:rounded-full after:bg-[#08c2cd]' : 'text-slate-300'}`}
                            >
                                {label}
                            </a>
                        ))}
                    </nav>

                    <div className="hidden items-center gap-4 text-xs text-slate-300 xl:flex">
                        <span className="flex items-center gap-1.5"><PinIcon /> CDMX · UPIICSA</span>
                    </div>

                    <div className="flex shrink-0 items-center gap-3">
                        <button
                            type="button"
                            onClick={() => document.getElementById('busqueda')?.focus()}
                            className="hidden rounded-lg p-2 text-slate-300 transition hover:bg-white/10 hover:text-[#08c2cd] sm:inline-flex"
                            aria-label="Ir a búsqueda"
                        >
                            <SearchIcon />
                        </button>
                        <a href="/carrito" className="relative rounded-lg p-2 text-[#08c2cd] transition hover:bg-white/10" aria-label={`Carrito, ${cartCount} artículos`}>
                            <BagIcon />
                            {cartCount > 0 && <span className="absolute -right-1 -top-1 grid h-4 min-w-4 place-items-center rounded-full bg-[#08c2cd] px-1 text-[9px] font-bold text-[#0b192c]">{cartCount}</span>}
                        </a>
                        <button
                            type="button"
                            onClick={() => setMenuOpen((open) => !open)}
                            aria-expanded={menuOpen}
                            aria-label="Abrir menú"
                            className="rounded-full bg-white/10 px-2.5 py-2 text-xs font-bold text-white transition hover:bg-white/20 lg:hidden"
                        >
                            Menú
                        </button>
                        {auth.user ? (
                            <div className="hidden items-center gap-2 sm:flex">
                                <a href="/cuenta" aria-label="Mi cuenta" className="grid h-9 w-9 place-items-center rounded-full bg-slate-700 text-xs font-bold text-white">{auth.user.name.slice(0, 2).toUpperCase()}</a>
                                <button type="button" onClick={() => router.post('/salir')} className="text-xs font-semibold text-slate-300 hover:text-white">Salir</button>
                            </div>
                        ) : <a href="/acceso" aria-label="Iniciar sesión" className="hidden h-9 w-9 place-items-center rounded-full bg-slate-700 text-xs font-bold text-white sm:grid">JP</a>}
                    </div>
                </div>
                {menuOpen && (
                    <nav className="grid gap-1 border-t border-white/10 bg-[#0b192c] px-5 py-3 lg:hidden" aria-label="Menú móvil">
                        {navLinks.map(([label, href]) => <a key={label} href={href} onClick={() => setMenuOpen(false)} className="rounded-lg px-3 py-2 text-sm text-slate-200 hover:bg-white/10">{label}</a>)}
                    </nav>
                )}
            </header>

            <main>
                <section id="inicio" className="relative isolate min-h-[440px] overflow-hidden bg-[#0b192c] sm:min-h-[480px]">
                    <div
                        className="absolute inset-0 -z-20 bg-cover bg-[center_58%]"
                        style={{ backgroundImage: "url('/images/hero-reef.jpg')" }}
                    />
                    <div className="absolute inset-0 -z-10 bg-gradient-to-r from-[#07182b]/95 via-[#07182b]/70 to-[#07182b]/25" />
                    <div className="mx-auto flex min-h-[440px] max-w-[1440px] items-center px-5 py-14 sm:min-h-[480px] sm:px-8">
                        <div className="max-w-3xl text-white">
                            <p className="text-xs font-extrabold uppercase tracking-[0.17em] text-[#08c2cd] sm:text-sm">Editorial marino contemporáneo</p>
                            <h1 className="mt-4 max-w-2xl text-4xl font-black leading-[1.08] tracking-tight sm:text-5xl lg:text-6xl">
                                Acuarismo responsable y biodiversidad en tu hogar
                            </h1>
                            <p className="mt-5 max-w-2xl text-base leading-7 text-slate-200 sm:text-lg">
                                Encuentra especímenes sanos con certificación biológica de origen, asesoría clínica veterinaria y sistemas de soporte vital premium.
                            </p>
                            <form onSubmit={searchProducts} className="mt-7 flex max-w-2xl items-center rounded-full bg-white p-1.5 shadow-2xl shadow-slate-950/30 ring-1 ring-white/50">
                                <SearchIcon />
                                <input
                                    id="busqueda"
                                    type="search"
                                    value={query}
                                    onChange={(event) => setQuery(event.target.value)}
                                    placeholder="¿Qué especie o equipo estás buscando hoy? Ej. Pez Cirujano, Acuario plantado..."
                                    className="min-w-0 flex-1 bg-transparent px-3 py-2.5 text-sm text-[#0b192c] outline-none placeholder:text-slate-500 sm:px-4"
                                />
                                <button type="submit" className="shrink-0 rounded-full bg-[#0b192c] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#173555]">Buscar</button>
                            </form>
                        </div>
                    </div>
                </section>

                <div className="mx-auto max-w-[1440px] px-5 sm:px-8">
                    <section id="catalogo" className="scroll-mt-24 py-12 sm:py-14">
                        <div className="flex items-end justify-between gap-4">
                            <div>
                                <p className="text-xs font-bold uppercase tracking-[0.14em] text-[#0ba8b5]">Explora AQUARIUM</p>
                                <h2 className="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">Categorías principales</h2>
                            </div>
                        </div>
                        <div className="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5 lg:gap-5">
                            {categories.map((category) => (
                                <button
                                    key={category.id}
                                    type="button"
                                    onClick={() => chooseCategory(category.id)}
                                    className="group relative aspect-[1.42] overflow-hidden rounded-2xl bg-[#0b192c] text-left shadow-sm transition hover:-translate-y-1 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-[#08c2cd]/40"
                                >
                                    <img src={category.image} alt="" loading="lazy" className="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                                    <span className="absolute inset-0 bg-gradient-to-t from-[#06111f]/95 via-[#06111f]/25 to-transparent" />
                                    <span className="absolute bottom-4 left-4 right-3 text-sm font-bold text-white sm:text-base">{category.title}</span>
                                    <span className="absolute bottom-1.5 left-4 text-[11px] font-semibold text-[#12d4dd] opacity-0 transition group-hover:bottom-3 group-hover:opacity-100">Explorar →</span>
                                </button>
                            ))}
                        </div>
                    </section>

                    <section id="especies" className="scroll-mt-24 pb-14 sm:pb-16">
                        <div className="flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <p className="text-xs font-bold uppercase tracking-[0.14em] text-[#0ba8b5]">Selección de especialistas</p>
                                <h2 className="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">Especies recomendadas</h2>
                                {(query || activeCategory) && <p className="mt-2 text-sm text-slate-500">{visibleProducts.length} resultados {query && <>para “{query}”</>}{activeCategory && <> en {categories.find((category) => category.id === activeCategory)?.title}</>}</p>}
                            </div>
                            <button
                                type="button"
                                onClick={() => { setActiveCategory(''); setQuery(''); }}
                                className="text-sm font-bold text-[#079fab] transition hover:text-[#0b192c]"
                            >
                                Ver todas las especies <span aria-hidden="true">→</span>
                            </button>
                        </div>
                        {visibleProducts.length > 0 ? (
                            <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:gap-5">
                                {visibleProducts.map((product) => (
                                    <ProductCard key={product.id} product={product} quantity={cart[product.id] ?? 0} onAdd={addToCart} />
                                ))}
                            </div>
                        ) : (
                            <div className="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-12 text-center">
                                <p className="font-bold">No encontramos coincidencias en esta selección.</p>
                                <p className="mt-1 text-sm text-slate-500">Prueba con otro nombre o consulta todas las especies.</p>
                                <button type="button" onClick={() => { setActiveCategory(''); setQuery(''); }} className="mt-4 rounded-lg bg-[#0b192c] px-4 py-2 text-sm font-bold text-white">Ver catálogo</button>
                            </div>
                        )}
                    </section>
                </div>

                <section id="clinica" className="scroll-mt-24 bg-[#e1f8f8]">
                    <div className="mx-auto flex max-w-[1440px] flex-col items-start gap-5 px-5 py-7 sm:flex-row sm:items-center sm:px-8 sm:py-8">
                        <span className="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-[#08c2cd] text-[#0b192c]">
                            <svg viewBox="0 0 40 40" aria-hidden="true" className="h-9 w-9 fill-none stroke-current stroke-[1.8]">
                                <path d="M20 33S5 24 5 14a8 8 0 0 1 15-4 8 8 0 0 1 15 4c0 10-15 19-15 19Z" />
                                <path d="M9 20h7l3-6 4 12 3-6h5" />
                            </svg>
                        </span>
                        <div className="flex-1">
                            <h2 className="text-xl font-extrabold sm:text-2xl">Centro Veterinario de Fauna Acuática Especializado</h2>
                            <p className="mt-1 max-w-4xl text-sm leading-6 text-slate-600 sm:text-base">
                                Nuestros biólogos y médicos veterinarios especialistas te apoyan en el diagnóstico de enfermedades, análisis de agua y cuarentena responsable de tus ejemplares. Agenda tu videollamada o visita.
                            </p>
                            <p className="mt-2 text-xs font-semibold text-slate-600">Horario de sucursal: 09:00–22:00 · Agenda de citas: 09:00–19:00, sujeta a disponibilidad veterinaria.</p>
                        </div>
                        <a href="/citas" className="shrink-0 rounded-xl bg-[#0b192c] px-5 py-3 text-center text-sm font-bold text-white transition hover:bg-[#173555]">
                            Agendar cita médica
                        </a>
                    </div>
                </section>

                <section id="informacion" className="scroll-mt-24 bg-slate-50 py-14 sm:py-20">
                    <div className="mx-auto max-w-[1440px] px-5 sm:px-8">
                        <p className="text-xs font-bold uppercase tracking-[0.14em] text-[#0ba8b5]">Lo que hacemos</p>
                        <div className="mt-2 grid gap-4 md:grid-cols-[1fr_1.2fr] md:items-end">
                            <h2 className="text-3xl font-extrabold tracking-tight sm:text-4xl">Información y servicios AQUARIUM</h2>
                            <p className="max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">Acompañamos a cada cliente para que sus especies y sistemas acuáticos se mantengan en condiciones adecuadas, desde la elección hasta el cuidado especializado.</p>
                        </div>
                        <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {[
                                { number: '01', title: 'Clínica veterinaria', body: 'Orientación y atención especializada para fauna acuática, diagnóstico y seguimiento clínico.', link: '#clinica', label: 'Conocer la clínica' },
                                { number: '02', title: 'Análisis y calidad del agua', body: 'Evaluación de parámetros del agua y recomendaciones para mejorar las condiciones del acuario.', link: 'mailto:info@aquarium.com.mx?subject=An%C3%A1lisis%20de%20agua', label: 'Solicitar información' },
                                { number: '03', title: 'Instalación de acuarios', body: 'Asesoría para planear e instalar sistemas acuáticos con equipo de soporte adecuado.', link: 'mailto:info@aquarium.com.mx?subject=Instalaci%C3%B3n%20de%20acuario', label: 'Consultar instalación' },
                                { number: '04', title: 'Asesoría biológica', body: 'Acompañamiento en selección responsable, compatibilidad y cuidados básicos de las especies.', link: '/catalogo', label: 'Explorar catálogo' },
                            ].map((service) => (
                                <article key={service.number} className="flex min-h-56 flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-900/[0.06]">
                                    <span className="text-xs font-extrabold tracking-widest text-[#0ba8b5]">SERVICIO {service.number}</span>
                                    <h3 className="mt-4 text-lg font-extrabold text-[#0b192c]">{service.title}</h3>
                                    <p className="mt-2 flex-1 text-sm leading-6 text-slate-600">{service.body}</p>
                                    <a href={service.link} className="mt-5 text-sm font-bold text-[#078b96] hover:text-[#0b192c]">{service.label} →</a>
                                </article>
                            ))}
                        </div>
                    </div>
                </section>
            </main>

            <footer className="bg-[#0b192c] text-slate-300">
                <div className="mx-auto grid max-w-[1440px] gap-10 px-5 py-11 sm:grid-cols-2 sm:px-8 lg:grid-cols-[1.6fr_1fr_1fr_1fr] lg:gap-14 lg:py-14">
                    <div>
                        <a href="#inicio" className="flex items-center gap-2.5 text-white">
                            <FishMark />
                            <span className="text-xl font-black tracking-tight">AQUARIUM</span>
                        </a>
                        <p className="mt-4 max-w-sm text-sm leading-6 text-slate-400">Comprometidos con el bienestar de la fauna acuática y la educación del acuarista mexicano bajo estrictas normas biológicas y de conservación.</p>
                    </div>
                    <div>
                        <h3 className="text-xs font-extrabold uppercase tracking-wider text-white">Fauna marina</h3>
                        <ul className="mt-4 space-y-2.5 text-sm text-slate-400">
                            <li><a href="#catalogo" onClick={() => chooseCategory('dulce')} className="hover:text-[#08c2cd]">Peces de Agua Dulce</a></li>
                            <li><a href="#catalogo" onClick={() => chooseCategory('salada')} className="hover:text-[#08c2cd]">Peces de Agua Salada</a></li>
                            <li><a href="#catalogo" className="hover:text-[#08c2cd]">Invertebrados</a></li>
                            <li><a href="#catalogo" className="hover:text-[#08c2cd]">Corales</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 className="text-xs font-extrabold uppercase tracking-wider text-white">Información</h3>
                        <ul className="mt-4 space-y-2.5 text-sm text-slate-400">
                            <li><a href="#informacion" className="hover:text-[#08c2cd]">Nuestros servicios</a></li>
                            <li><a href="#clinica" className="hover:text-[#08c2cd]">Clínica Veterinaria</a></li>
                            <li><a href="mailto:info@aquarium.com.mx" className="hover:text-[#08c2cd]">Análisis de agua e instalación</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 className="text-xs font-extrabold uppercase tracking-wider text-white">Contacto</h3>
                        <p className="mt-4 text-sm text-slate-400">Soporte 24/7 · CDMX</p>
                        <a href="mailto:info@aquarium.com.mx" className="mt-2 inline-block text-sm font-medium text-[#08c2cd] hover:text-white">info@aquarium.com.mx</a>
                    </div>
                </div>
                <div className="border-t border-white/10">
                    <div className="mx-auto flex max-w-[1440px] flex-col gap-3 px-5 py-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                        <p>© 2026 AQUARIUM México S.A. de C.V. Todos los derechos reservados. Registro de Fauna SEMARNAT-043-26.</p>
                        <a href="#inicio" className="font-semibold text-slate-400 hover:text-[#08c2cd]">Volver al inicio ↑</a>
                    </div>
                </div>
            </footer>

            {notice && <div role="status" aria-live="polite" className="fixed bottom-5 right-5 z-50 max-w-sm rounded-xl bg-[#0b192c] px-5 py-3 text-sm font-semibold text-white shadow-2xl ring-1 ring-white/15">✓ {notice}</div>}
        </div>
    );
}

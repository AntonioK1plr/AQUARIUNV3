import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';

const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

function StoreHeader({ count = 0 }) {
    return <header className="bg-[#0b192c] text-white"><div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4"><Link href="/" className="text-lg font-black tracking-tight">AQUARIUM</Link><nav className="flex items-center gap-4 text-sm" aria-label="Navegación principal"><Link href="/" className="text-slate-300 hover:text-white">Inicio</Link><Link href="/catalogo" className="text-slate-300 hover:text-white">Catálogo</Link><Link href="/#clinica" className="hidden text-slate-300 hover:text-white sm:inline">Clínica</Link><Link href="/#informacion" className="hidden text-slate-300 hover:text-white sm:inline">Información</Link><Link href="/carrito" className="font-bold text-[#08c2cd]">Carrito{count ? ` (${count})` : ''}</Link></nav></div></header>;
}

export default function CartIndex({ lines = [], totals = { subtotal: '0.00', tax: '0.00', total: '0.00' }, authUser, compatibilityWarnings = [], careAcceptedProductIds = [], reservedUntil = {} }) {
    const { props } = usePage();
    const [fulfillment, setFulfillment] = useState('click_collect');
    const [payment, setPayment] = useState('tarjeta_simulada');
    const [compatibilityConfirmed, setCompatibilityConfirmed] = useState(false);
    const [shippingAddress, setShippingAddress] = useState({ recipient: authUser?.name || '', phone: '', street: '', exterior_number: '', interior_number: '', neighborhood: '', postal_code: '', city: '', state: '', instructions: '' });
    const errors = props.errors || {};
    const count = lines.reduce((sum, line) => sum + Number(line.quantity), 0);

    function setQuantity(productId, quantity) {
        const value = Number(quantity);
        if (value > 0) router.patch(`/carrito/items/${productId}`, { quantity: value }, { preserveScroll: true });
        else router.delete(`/carrito/items/${productId}`, { preserveScroll: true });
    }

    function checkout(event) {
        event.preventDefault();
        router.post('/carrito/checkout', {
            fulfillment_type: fulfillment,
            payment_method: payment,
            compatibility_confirmed: compatibilityConfirmed,
            ...(fulfillment === 'envio' ? { shipping_address: shippingAddress } : {}),
        });
    }

    return <div className="min-h-screen bg-slate-50 text-[#0b192c]">
        <Head title="Carrito · AQUARIUM" />
        <StoreHeader count={count} />
        <main className="mx-auto max-w-7xl px-5 py-8 sm:py-12">
            <p className="text-xs font-bold uppercase tracking-[0.14em] text-teal-700">Compra responsable</p>
            <h1 className="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Tu carrito</h1>
            <p className="mt-2 text-sm text-slate-600">Revisa tus productos y selecciona cómo recibirlos. El pago en esta versión es solo una simulación local.</p>

            {props.flash?.success && <div role="status" className="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{props.flash.success}</div>}
            {props.flash?.warning && <div role="status" className="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">{props.flash.warning}</div>}
            {errors.cart && <div role="alert" className="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{errors.cart}</div>}
            {errors.product && <div role="alert" className="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{errors.product}</div>}
            {errors.care && <div role="alert" className="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{errors.care}</div>}
            {errors.compatibility && <div role="alert" className="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{errors.compatibility}</div>}
            {compatibilityWarnings.length > 0 && <section className="mt-6 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950" role="alert">
                <h2 className="font-extrabold">Revisa la compatibilidad de tus ejemplares</h2>
                <p className="mt-1 text-sm">La combinación puede requerir condiciones distintas. Esta advertencia no sustituye la asesoría de un especialista.</p>
                <ul className="mt-3 list-disc space-y-1 pl-5 text-sm">{compatibilityWarnings.map((warning, index) => <li key={index}><b>{warning.products.join(' + ')}</b>: {warning.reasons.join('; ')}.</li>)}</ul>
            </section>}

            {!lines.length ? <section className="mt-7 rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center shadow-sm"><div className="mx-auto grid h-14 w-14 place-items-center rounded-full bg-teal-50 text-2xl text-teal-700" aria-hidden="true">♧</div><h2 className="mt-4 text-xl font-extrabold">Tu carrito está vacío</h2><p className="mt-2 text-sm text-slate-600">Encuentra peces e insumos para continuar.</p><Link href="/catalogo" className="mt-5 inline-flex rounded-xl bg-[#0b192c] px-5 py-3 text-sm font-bold text-white hover:bg-[#173555]">Explorar catálogo</Link></section> : <div className="mt-7 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                <section className="space-y-3" aria-label="Productos seleccionados">
                    {lines.map(({ product, quantity }) => <article key={product.id} className="flex flex-col gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-900/10 sm:flex-row sm:items-center">
                        <img src={product.image_path || '/images/hero-reef.jpg'} onError={(event) => { event.currentTarget.onerror = null; event.currentTarget.src = '/images/hero-reef.jpg'; }} alt={product.name} className="h-28 w-full rounded-xl bg-slate-100 object-cover sm:w-36" />
                        <div className="min-w-0 flex-1"><p className="text-xs font-bold uppercase tracking-wide text-teal-700">{product.category}</p><h2 className="mt-1 font-extrabold">{product.name}</h2><p className="mt-1 text-sm text-slate-600">SKU {product.sku} · {money.format(Number(product.price))} por unidad</p><p className="mt-2 text-sm font-bold">Importe: {money.format(Number(product.price) * Number(quantity))}</p></div>
                        <label className="text-xs font-bold text-slate-600">Cantidad<input aria-label={`Cantidad de ${product.name}`} type="number" min="1" max={product.available_stock ?? product.stock} defaultValue={quantity} onBlur={(event) => { if (Number(event.target.value) !== Number(quantity)) setQuantity(product.id, event.target.value); }} onKeyDown={(event) => { if (event.key === 'Enter') event.currentTarget.blur(); }} className="mt-1 block w-24 rounded-lg border-slate-300 text-sm text-[#0b192c]" /><span className="mt-1 block font-normal">Disponibles para ti: {product.available_stock ?? product.stock}</span>{reservedUntil[product.id] && <span className="mt-1 block font-semibold text-teal-800">Apartado hasta {new Date(reservedUntil[product.id]).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}</span>}</label>
                        <button type="button" onClick={() => router.delete(`/carrito/items/${product.id}`, { preserveScroll: true })} className="self-start rounded-lg px-3 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50 sm:self-center">Quitar</button>
                    </article>)}
                    {lines.some(({ product }) => ['pez', 'invertebrado', 'coral'].includes(product.category)) && <div className="rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-950"><p className="font-extrabold">Cuidado responsable</p><p className="mt-1">La aceptación de cuidado de cada ejemplar vivo se solicita antes de agregarlo y se guardará con el pedido.</p><p className="mt-2 text-xs">Aceptaciones en esta sesión: {careAcceptedProductIds.length}</p></div>}
                    <Link href="/catalogo" className="inline-flex py-2 text-sm font-bold text-teal-800 hover:underline">← Seguir explorando</Link>
                </section>

                <aside className="h-fit rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-900/10 lg:sticky lg:top-5">
                    <h2 className="text-lg font-extrabold">Resumen de compra</h2>
                    <dl className="mt-4 space-y-2 border-b border-slate-100 pb-4 text-sm"><div className="flex justify-between"><dt className="text-slate-600">Productos ({count})</dt><dd className="font-semibold">{money.format(Number(totals.subtotal))}</dd></div><div className="flex justify-between"><dt className="text-slate-600">IVA (16%)</dt><dd className="font-semibold">{money.format(Number(totals.tax))}</dd></div><div className="flex justify-between pt-2 text-base"><dt className="font-extrabold">Total</dt><dd className="font-black">{money.format(Number(totals.total))}</dd></div></dl>
                    {!authUser ? <div className="mt-4 rounded-xl bg-slate-50 p-4"><p className="text-sm text-slate-600">Inicia sesión o crea una cuenta de cliente para confirmar tu pedido.</p><Link href="/acceso?continuar=carrito" className="mt-3 block rounded-lg bg-[#0b192c] px-4 py-3 text-center text-sm font-bold text-white hover:bg-[#173555]">Continuar al acceso</Link><Link href="/registro?continuar=carrito" className="mt-3 block text-center text-sm font-bold text-teal-800 hover:underline">Crear cuenta</Link></div> : <form onSubmit={checkout} className="mt-5 space-y-4">
                        <fieldset><legend className="text-sm font-extrabold">Método de entrega</legend><label className="mt-2 flex cursor-pointer gap-3 rounded-xl border p-3 text-sm has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50"><input type="radio" name="fulfillment" value="click_collect" checked={fulfillment === 'click_collect'} onChange={() => setFulfillment('click_collect')} className="mt-0.5 text-teal-700" /><span><b>Click & Collect</b><span className="block text-xs text-slate-500">Recoge en tienda con un código digital.</span></span></label><label className="mt-2 flex cursor-pointer gap-3 rounded-xl border p-3 text-sm has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50"><input type="radio" name="fulfillment" value="envio" checked={fulfillment === 'envio'} onChange={() => setFulfillment('envio')} className="mt-0.5 text-teal-700" /><span><b>Envío</b><span className="block text-xs text-slate-500">Entrega; configuración logística local pendiente.</span></span></label></fieldset>
                        {fulfillment === 'envio' && <fieldset className="rounded-xl border border-slate-200 p-3"><legend className="px-1 text-sm font-extrabold">Dirección de entrega</legend><p className="mb-3 text-xs leading-5 text-slate-500">La dirección se guarda en el pedido para esta simulación local.</p><div className="grid gap-3 sm:grid-cols-2">
                            {[
                                ['recipient', 'Recibe'], ['phone', 'Teléfono de contacto'], ['street', 'Calle'], ['exterior_number', 'Número exterior'], ['interior_number', 'Número interior (opcional)'], ['neighborhood', 'Colonia'], ['postal_code', 'Código postal (5 dígitos)'], ['city', 'Alcaldía o municipio'], ['state', 'Estado'],
                            ].map(([key, label]) => <label key={key} className="text-xs font-bold text-slate-700">{label}<input required={key !== 'interior_number'} maxLength={key === 'phone' ? 20 : 120} inputMode={key === 'phone' || key === 'postal_code' ? 'tel' : 'text'} value={shippingAddress[key]} onChange={(event) => setShippingAddress((current) => ({ ...current, [key]: event.target.value }))} className="mt-1 block w-full rounded-lg border-slate-300 text-sm font-normal" />{errors[`shipping_address.${key}`] && <span className="mt-1 block font-normal text-rose-700">{errors[`shipping_address.${key}`]}</span>}</label>)}
                            <label className="text-xs font-bold text-slate-700 sm:col-span-2">Referencias e indicaciones (opcional)<textarea maxLength="500" rows="2" value={shippingAddress.instructions} onChange={(event) => setShippingAddress((current) => ({ ...current, instructions: event.target.value }))} className="mt-1 block w-full rounded-lg border-slate-300 text-sm font-normal" /></label>
                        </div></fieldset>}
                        <label className="block text-sm font-extrabold">Simulación de pago<select value={payment} onChange={(event) => setPayment(event.target.value)} className="mt-2 w-full rounded-lg border-slate-300 text-sm font-normal"><option value="tarjeta_simulada">Tarjeta simulada</option><option value="transferencia_simulada">Transferencia simulada</option></select></label>
                        <p className="rounded-lg bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-900">No ingreses datos bancarios. No se conecta con una pasarela ni se realiza ningún cargo.</p>
                        {compatibilityWarnings.length > 0 && <label className="flex cursor-pointer gap-2 rounded-lg border border-amber-200 p-3 text-xs leading-5 text-slate-700"><input type="checkbox" checked={compatibilityConfirmed} onChange={(event) => setCompatibilityConfirmed(event.target.checked)} className="mt-1 rounded border-slate-300 text-teal-700 focus:ring-teal-600" /><span>Confirmo que revisé y comprendí las advertencias de compatibilidad. La combinación queda registrada en el pedido.</span></label>}
                        <button type="submit" disabled={compatibilityWarnings.length > 0 && !compatibilityConfirmed} className="w-full rounded-xl bg-[#08c2cd] px-4 py-3 text-sm font-extrabold text-[#0b192c] hover:bg-[#20d5dc] disabled:cursor-not-allowed disabled:opacity-50">Confirmar pedido simulado</button>
                    </form>}
                </aside>
            </div>}
        </main>
        <footer className="mt-12 bg-[#0b192c] px-5 py-6 text-center text-sm text-slate-400">AQUARIUM · Compra de prueba en entorno local</footer>
    </div>;
}

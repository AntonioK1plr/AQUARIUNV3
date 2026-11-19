import React, { useEffect, useRef, useState } from 'react';

const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

export default function StripePaymentPanel({ stripeKey, intent, onPaid }) {
    const isTestMode = (intent.mode || 'test') === 'test';
    const mountRef = useRef(null);
    const stripeRef = useRef(null);
    const elementsRef = useRef(null);
    const [ready, setReady] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [message, setMessage] = useState('');

    useEffect(() => {
        let element;
        let cancelled = false;
        const mountPaymentElement = async () => {
            if (!window.Stripe) {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://js.stripe.com/v3/';
                    script.async = true;
                    script.onload = resolve;
                    script.onerror = () => reject(new Error('No se pudo cargar el formulario seguro de Stripe.'));
                    document.head.appendChild(script);
                });
            }
            if (cancelled || !mountRef.current) return;
            stripeRef.current = window.Stripe(stripeKey);
            elementsRef.current = stripeRef.current.elements({
                clientSecret: intent.client_secret,
                appearance: {
                    theme: 'stripe',
                    variables: { colorPrimary: '#08aeb8', colorText: '#0b192c', borderRadius: '10px', fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif' },
                },
            });
            element = elementsRef.current.create('payment', { layout: 'tabs' });
            element.mount(mountRef.current);
            setReady(true);
        };

        mountPaymentElement().catch((error) => setMessage(error.message));
        return () => {
            cancelled = true;
            element?.unmount();
        };
    }, [intent.client_secret, stripeKey]);

    async function pay(event) {
        event.preventDefault();
        if (!stripeRef.current || !elementsRef.current) return;
        setProcessing(true);
        setMessage('');
        const { error, paymentIntent } = await stripeRef.current.confirmPayment({
            elements: elementsRef.current,
            confirmParams: { return_url: intent.return_url || `${window.location.origin}/cuenta/pedidos` },
            redirect: 'if_required',
        });
        if (error) {
            setMessage(error.message || 'Stripe no pudo confirmar el pago. Revisa los datos e intenta de nuevo.');
            setProcessing(false);
            return;
        }
        if (paymentIntent?.status === 'succeeded') {
            setMessage(isTestMode ? 'Stripe aprobó la prueba. AQUARIUM espera el webhook para confirmar el registro.' : 'Stripe confirmó el pago. AQUARIUM espera el webhook para confirmar el registro.');
            onPaid?.();
            return;
        }
        setMessage(`Estado recibido: ${paymentIntent?.status || 'pendiente'}. La operación espera la confirmación de Stripe.`);
        setProcessing(false);
    }

    return <section className="rounded-xl border border-teal-200 bg-white p-4"><div className="flex flex-wrap items-start justify-between gap-2"><div><h3 className="text-sm font-extrabold">Pago con Stripe</h3><p className="mt-1 text-xs text-slate-500">{intent.order_number} · {money.format(Number(intent.amount))} MXN</p></div><span className={`rounded-full px-2.5 py-1 text-[10px] font-extrabold ${isTestMode ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-900'}`}>{isTestMode ? 'MODO PRUEBA' : 'PAGO SEGURO'}</span></div><div ref={mountRef} className="mt-4 min-h-12" />{message && <p role="status" className={`mt-3 rounded-lg px-3 py-2 text-xs leading-5 ${message.includes('aprobó') || message.includes('confirmó el pago') ? 'bg-emerald-50 text-emerald-900' : 'bg-amber-50 text-amber-900'}`}>{message}</p>}<button type="button" onClick={pay} disabled={!ready || processing} className="mt-4 w-full rounded-xl bg-[#08c2cd] px-4 py-3 text-sm font-extrabold text-[#0b192c] hover:bg-[#20d5dc] disabled:cursor-wait disabled:opacity-50">{processing ? 'Enviando a Stripe…' : 'Pagar con tarjeta'}</button>{isTestMode && <p className="mt-3 text-[11px] leading-4 text-slate-500">Modo de prueba: usa una tarjeta de prueba, nunca una tarjeta real. Stripe recibe los datos directamente; AQUARIUM no almacena el número ni el CVC.</p>}</section>;
}

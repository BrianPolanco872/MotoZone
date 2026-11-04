const form = document.querySelector('[data-product-form]');

if (form) {
    const cost = form.querySelector('#cost');
    const margin = form.querySelector('#margin');
    const price = form.querySelector('#price');
    const taxRate = form.querySelector('#tax_rate');

    let updating = false;

    const round = (value) => Math.round(value * 100) / 100;

    const calculatePrice = () => {
        if (!cost || !margin || !price || updating) {
            return;
        }

        const costValue = Number.parseFloat(cost.value);
        const marginValue = Number.isNaN(Number.parseFloat(margin.value)) ? 0 : Number.parseFloat(margin.value);
        const taxRateValue = taxRate && !Number.isNaN(Number.parseFloat(taxRate.value)) ? Number.parseFloat(taxRate.value) : 0;

        if (!Number.isFinite(costValue) || costValue <= 0) {
            return;
        }

        updating = true;
        // Costo + Ganancia (%)
        let basePrice = costValue * (1 + marginValue / 100);
        
        // Sobre el resultado, aplicamos el IVA (%)
        let finalPrice = basePrice * (1 + taxRateValue / 100);
        
        price.value = round(finalPrice).toFixed(2);
        updating = false;
    };

    const calculateMargin = () => {
        if (!cost || !margin || !price || updating) {
            return;
        }

        const costValue = Number.parseFloat(cost.value);
        const priceValue = Number.parseFloat(price.value);

        if (!Number.isFinite(costValue) || !Number.isFinite(priceValue) || costValue <= 0) {
            return;
        }

        updating = true;
        margin.value = round(((priceValue - costValue) / costValue) * 100).toFixed(2);
        updating = false;
    };

    cost?.addEventListener('input', () => {
        calculatePrice();
    });

    margin?.addEventListener('input', calculatePrice);
    taxRate?.addEventListener('input', calculatePrice);
    price?.addEventListener('input', calculateMargin);

    // Forzar cálculo inicial si ya hay un costo al cargar la página
    if (cost && cost.value > 0) {
        calculatePrice();
    }
}

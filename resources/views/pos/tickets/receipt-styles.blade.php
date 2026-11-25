        .ticket-sheet {
            box-sizing: border-box;
            width: var(--ticket-width, 80mm);
            max-width: 100%;
            margin: 0 auto;
            padding: 4mm;
            background: #fff;
            color: #000;
            font: 11px / 1.3 "Courier New", ui-monospace, monospace;
            font-variant-numeric: tabular-nums;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .15);
        }
        .ticket-sheet .center { text-align: center; }
        .ticket-sheet p { margin: 0; overflow-wrap: anywhere; }
        .ticket-sheet .business { font-size: 13px; font-weight: 700; text-transform: uppercase; }
        .ticket-sheet .muted { font-size: 10px; }
        .ticket-sheet .divider { border: 0; border-top: 1px dashed #000; margin: 1.5mm 0; }
        .ticket-sheet table { width: 100%; border-collapse: collapse; table-layout: auto; }
        .ticket-sheet th { text-align: left; }
        .ticket-sheet td, .ticket-sheet th { padding: .5mm 0; vertical-align: top; }
        .ticket-sheet thead th { font-size: 10px; text-transform: uppercase; }
        .ticket-sheet .items .description { width: 100%; }
        .ticket-sheet .description, .ticket-sheet .label { overflow-wrap: anywhere; }
        .ticket-sheet .num { text-align: right; white-space: nowrap; padding-left: 1.5mm; }
        .ticket-sheet .items .num { font-size: 10px; }
        .ticket-sheet .items .quantity { padding-left: 0; padding-right: 1.5mm; }
        .ticket-sheet .item-name, .ticket-sheet .item-detail { display: block; }
        .ticket-sheet .item-name { font-weight: 700; }
        .ticket-sheet .total-row td { font-size: 13px; font-weight: 700; }
        .ticket-sheet .receipt-logo { display: inline-block; border: 1px solid; padding: 1mm 2mm; font: bold 16px sans-serif; }
        .ticket-sheet .receipt-footer { white-space: pre-line; }
        .ticket-sheet .receipt-qr { display: block; width: 24mm; height: 24mm; object-fit: contain; margin: 2mm auto 0; }
        @media print {
            @page { size: auto; margin: 4mm; }
            .ticket-sheet { width: calc(var(--ticket-width, 80mm) - 10mm); padding: 0; box-shadow: none; }
            .ticket-sheet tr, .ticket-sheet .receipt-heading, .ticket-sheet .receipt-summary, .ticket-sheet .receipt-end { break-inside: avoid; }
        }

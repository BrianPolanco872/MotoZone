<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\UpdateTicketSettingsRequest;
use App\Services\TicketSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class TicketSettingsController extends Controller
{
    public function edit(TicketSettings $tickets): View
    {
        return view('pos.tickets.settings', ['settings' => $tickets->current()]);
    }

    public function update(UpdateTicketSettingsRequest $request, TicketSettings $tickets): RedirectResponse
    {
        $data = $request->safe()->only(['business', 'ticket']);
        $data['business'] = array_map(fn ($value): string => $value ?? '', $data['business']);
        $data['ticket']['width_mm'] = (int) $data['ticket']['width_mm'];

        foreach (['show_logo', 'show_tax_id', 'show_sku', 'show_cashier', 'show_tax', 'show_qr'] as $key) {
            $data['ticket'][$key] = $request->boolean('ticket.'.$key);
        }

        $data['ticket']['footer'] ??= '';
        $data['ticket']['policy'] ??= '';
        $data['ticket']['qr_image_url'] = $tickets->current()['ticket']['qr_image_url'];

        if ($request->hasFile('qr_image')) {
            $data['ticket']['qr_image_url'] = 'data:image/png;base64,'.base64_encode($request->file('qr_image')->get());
        }

        if ($data['ticket']['show_qr'] && ! $data['ticket']['qr_image_url']) {
            throw ValidationException::withMessages(['qr_image' => 'Carga un código QR real en formato PNG para mostrarlo en el ticket.']);
        }

        $tickets->save($data);

        return to_route('pos.tickets.edit')->with('status', 'Configuración guardada. Se aplicará a los tickets del POS.');
    }
}

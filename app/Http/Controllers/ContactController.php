<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use App\Http\Resources\ContactResource;

class ContactController extends Controller
{
    //

    public function getContact(Request $request)
    {
        $contacts = $request->user()->contacts;
        if($contacts->isNotEmpty()) {
            return response()->json(
                ContactResource::collection($contacts)
            );
        }
        return response()->json(['message' => 'No tiene contactos'], 404);
    }

    public function deleteContact(Request $request, int $id)
    {
        $contact = $request->user()->contacts()->find($id);
        if ($contact === null) {
            return response()->json(['message' => 'Contacto no encontrado'], 404);
        }
        $contact->delete();
        return response()->json(['message' => 'Contacto eliminado correctamente']);
    }

    public function putContact(Request $request, int $id)
    {
        $contact = $request->user()->contacts()->find($id);
        $contact_email = $request->user()->contacts()->where('email', $request->input('email'))->first();
        $contact_number = $request->user()->contacts()->where('phone_number', $request->input('phone_number'))->first();

        if (($contact_email != null && $contact_email->id != $id) || ($contact_number != null && $contact_number->id != $id)) {
            return response()->json(['message' => 'El correo o número de teléfono del contacto ya existe'], 409);
        }

        if ($contact === null) {
            return response()->json(['message' => 'Contacto no encontrado'], 404);
        }
        $contact->update($request->all());
        return response()->json(['message' => 'Contacto actualizado correctamente']);
    }

    public function postContact(Request $request)
    {
        $contact_email = $request->user()->contacts()->where('email', $request->input('email'))->first();
        $contact_number = $request->user()->contacts()->where('phone_number', $request->input('phone_number'))->first();
        
        if ($contact_email != null || $contact_number != null) {
            return response()->json(['message' => 'El correo o número de teléfono del contacto ya existe'], 409);
        }
        $contact = $request->user()->contacts()->create($request->all());
        return response()->json(['message' => 'Contacto creado correctamente', 'contact' => new ContactResource($contact)]);
    }
    
}

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
}

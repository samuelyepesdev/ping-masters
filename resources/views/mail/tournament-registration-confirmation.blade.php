@component('mail::message')
# ¡Inscripción confirmada!

Hola {{ $user->name }},

Tu inscripción a **{{ $tournament->name }}** fue recibida correctamente.

@if (count($divisionNames) > 0)
**Categorías:**
@foreach ($divisionNames as $name)
- {{ $name }}
@endforeach
@endif

@if ($tournament->venue || $tournament->city)
**Sede:** {{ trim(($tournament->venue ?? '').' '.($tournament->city ? "— {$tournament->city}" : '')) }}
@endif

@if ($tournament->start_date)
**Fecha:** {{ $tournament->start_date->format('d/m/Y') }}
@endif

Un organizador revisará tu inscripción pronto.

@isset($setPasswordUrl)
---

Como parte de tu inscripción, creamos una cuenta para ti en Ping Masters con este correo ({{ $user->email }}). Para poder ingresar a la plataforma, primero define tu contraseña:

@component('mail::button', ['url' => $setPasswordUrl])
Crear mi contraseña
@endcomponent

Este enlace expira en 60 minutos.
@endisset

Gracias,<br>
{{ config('app.name') }}
@endcomponent

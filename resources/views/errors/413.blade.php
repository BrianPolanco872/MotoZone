<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Archivo demasiado grande · MotoZone</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #111318; color: #fff; font: 1rem/1.6 system-ui, sans-serif; }
        main { max-width: 34rem; padding: 2rem; }
        h1, a { color: #f97316; }
    </style>
</head>
<body>
    <main>
        <h1>No se pudo recibir el archivo</h1>
        <p>La petición supera el límite del servidor. En el editor de inicio, cada foto puede pesar hasta 20 MB. Prueba con una imagen más pequeña o pega un enlace HTTPS.</p>
        <p>Vuelve al editor y revisa qué contenido quedó guardado antes de intentarlo otra vez.</p>
        <a href="{{ route('admin.home.edit') }}">Volver a Editar inicio</a>
    </main>
</body>
</html>

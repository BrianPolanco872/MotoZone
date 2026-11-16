<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso denegado · MotoZone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #1a1a1a;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #ffffff;
        }

        .error-container {
            text-align: center;
            padding: 2rem;
            max-width: 600px;
        }

        .error-code {
            font-size: 3rem;
            font-weight: 800;
            color: #f59e0b;
            margin-bottom: 1rem;
            display: block;
        }

        .error-message {
            font-size: 1.25rem;
            font-weight: 600;
            color: #f59e0b;
            margin-bottom: 0.5rem;
        }

        .error-description {
            font-size: 1rem;
            color: #9ca3af;
            margin-bottom: 2rem;
        }

        .error-icon {
            font-size: 4rem;
            color: #f59e0b;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔒</div>
        <span class="error-code">403</span>
        <div class="error-message">Acceso denegado</div>
        <div class="error-description">
            No tienes permiso para acceder a este módulo. Pide al administrador que te asigne el permiso correspondiente.
        </div>
    </div>
</body>
</html>

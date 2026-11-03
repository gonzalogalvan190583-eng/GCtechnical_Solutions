# Diagramas de GC Technical Solutions

Estos diagramas describen la navegación pública y el panel privado de solicitudes.

## Diagrama de flujo

```mermaid
flowchart TD
    A([Visitante ingresa al sitio]) --> B[Explora servicios y contenido]
    B --> C{¿Desea solicitar información?}
    C -->|No| B
    C -->|Sí| D[Abre el formulario de contacto]
    D --> E[Completa nombre, correo, asunto y mensaje]
    E --> F{¿Campos válidos?}
    F -->|No| E
    F -->|Sí| G[PHP valida y guarda la solicitud en MySQL]
    G --> H{¿Se guardó correctamente?}
    H -->|Sí| I[Muestra confirmación]
    H -->|No| J[Muestra un error de envío]
    I --> K[El equipo revisa solicitudes en el panel por fecha]
    K --> L[Abre Outlook para responder al cliente]
    L --> M[Marca la solicitud como respondida o realizada]
```

## Diagrama de casos de uso

```mermaid
flowchart LR
    visitante([Visitante])
    usuario([Usuario autenticado])

    subgraph sitio["Sitio público"]
        uc1((Explorar contenido))
        uc2((Consultar servicios))
        uc3((Buscar servicios))
        uc4((Ver portafolio y equipo))
        uc5((Solicitar información))
        uc6((Guardar solicitud en MySQL))
        uc7((Contactar por WhatsApp o correo))
    end

    subgraph gestion["Panel privado"]
        uc8((Consultar solicitudes por fecha))
        uc9((Responder en Outlook))
        uc10((Cambiar estado))
        uc11((Gestionar usuarios según permisos))
    end

    visitante --- uc1
    visitante --- uc2
    visitante --- uc3
    visitante --- uc4
    visitante --- uc5
    visitante --- uc7
    uc5 -. incluye .-> uc6
    usuario --- uc8
    usuario --- uc9
    usuario --- uc10
    usuario --- uc11
```

## Diagrama entidad-relación

El asunto conserva el servicio seleccionado desde el formulario. Las solicitudes no crean cuentas de cliente.

```mermaid
erDiagram
    USERS ||--o{ SERVICE_REQUESTS : responds_to
    USERS ||--o{ SERVICE_REQUESTS : completes

    USERS {
        int id PK
        string username UK
        string display_name
        string password_hash
        enum role
        boolean must_change_password
    }

    SERVICE_REQUESTS {
        int id PK
        string name
        string email
        string subject
        text message
        enum status
        datetime created_at
        datetime responded_at
        int responded_by FK
        datetime completed_at
        int completed_by FK
    }
```

## Alcance implementado

- Los botones de los servicios completan el asunto del formulario.
- PHP valida y persiste las solicitudes en MySQL.
- El panel requiere autenticación y permite consultar por fecha, actualizar estados y abrir Outlook para responder.
- Las cuentas internas se crean desde el panel o mediante el comando inicial de administración.
- El formulario de suscripción del pie de página está comentado y no forma parte del flujo activo.

# Diagramas de GC Technical Solutions

Los siguientes diagramas describen la navegación y las funciones visibles del sitio actual. El diagrama entidad-relación es un **modelo conceptual propuesto**: el proyecto no tiene una base de datos ni persiste clientes o consultas.

## Diagrama de flujo

```mermaid
flowchart TD
    A([Visitante ingresa al sitio]) --> B[Explora inicio, nosotros, servicios, portafolio, equipo o preguntas frecuentes]
    B --> C{¿Qué desea hacer?}
    C -->|Buscar un servicio| D[Escribe en el buscador]
    D --> E{¿Hay coincidencias?}
    E -->|Sí| F[Selecciona un resultado]
    F --> G[Abre la página del servicio]
    E -->|No| H[Ve que no hay resultados]
    H --> D
    C -->|Solicitar información| I[Abre el formulario de contacto]
    G --> J{¿Desea contactar?}
    J -->|Sí| I
    J -->|No| B
    C -->|Contacto directo| K[Abre WhatsApp o correo]
    I --> L[Completa nombre, correo, asunto y mensaje]
    L --> M{¿Campos obligatorios completos?}
    M -->|No| L
    M -->|Sí| N[El sitio intenta enviar el formulario mediante EmailJS]
    N --> O{¿El envío responde correctamente?}
    O -->|Sí| P[Muestra confirmación y limpia el formulario]
    O -->|No| Q[Muestra error de envío]
```

## Diagrama de casos de uso

```mermaid
flowchart LR
    visitante([Visitante])
    emailjs([Servicio externo EmailJS])

    subgraph sitio["GC Technical Solutions"]
        uc1((Explorar contenido))
        uc2((Consultar servicios))
        uc3((Buscar servicios))
        uc4((Ver portafolio y equipo))
        uc5((Solicitar información))
        uc6((Enviar mensaje de contacto))
        uc7((Contactar por WhatsApp o correo))
    end

    visitante --- uc1
    visitante --- uc2
    visitante --- uc3
    visitante --- uc4
    visitante --- uc5
    visitante --- uc7
    uc5 -. incluye .-> uc6
    uc6 -. intenta enviar mediante .-> emailjs
```

## Diagrama entidad-relación conceptual

Este modelo sirve como base si más adelante se incorpora persistencia. `servicio_id` sería opcional: el formulario actual no registra qué servicio originó la consulta.

```mermaid
erDiagram
    CLIENTE ||--o{ CONSULTA : realiza
    SERVICIO o|--o{ CONSULTA : relacionado_con

    CLIENTE {
        int cliente_id PK
        string nombre
        string email
    }

    SERVICIO {
        int servicio_id PK
        string nombre
        string descripcion
        string url
    }

    CONSULTA {
        int consulta_id PK
        int cliente_id FK
        int servicio_id FK "opcional"
        string asunto
        string mensaje
    }
```

### Alcance actual

- La búsqueda del menú filtra servicios definidos en JavaScript y abre la página seleccionada.
- El formulario de contacto recoge nombre, correo, asunto y mensaje. Su integración usa identificadores de EmailJS de ejemplo, por lo que requiere configuración para enviar mensajes.
- No se guardan clientes ni consultas en una base de datos.
- El formulario de suscripción del pie de página está comentado y no forma parte del flujo activo.

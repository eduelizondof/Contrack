# Documentación: Store y Service del Módulo de Chat

## 📋 Resumen General

El módulo de chat utiliza una arquitectura basada en **Pinia Store** para el manejo de estado global y un **Service** para la comunicación con el backend. Esta separación permite mantener la lógica de negocio separada de las llamadas HTTP.

---

## 🗂️ Store de Chat (`stores/chat.js`)

### Propósito
Gestiona el estado global del módulo de chat, incluyendo conversaciones, mensajes, y operaciones relacionadas.

### Estado (State)

```javascript
{
  conversaciones: [],              // Lista de todas las conversaciones
  conversacionActiva: null,        // Conversación actualmente seleccionada
  mensajes: [],                    // Mensajes de la conversación activa
  cargando: false,                 // Estado de carga de conversaciones
  cargandoMensajes: false,         // Estado de carga de mensajes
  tieneMasMensajes: false,         // Indica si hay más mensajes para cargar
  sidebarAbierto: false,           // Estado del sidebar (mobile)
  pollingInterval: null,           // Intervalo de polling global
  enviandoMensaje: false           // Estado de envío de mensaje
}
```

### Getters (Computed)

#### `conversacionesActivas`
Filtra y retorna solo las conversaciones que no están archivadas.

#### `conversacionesArchivadas`
Filtra y retorna solo las conversaciones archivadas.

#### `mensajesNoLeidos`
Calcula el total de mensajes no leídos de todas las conversaciones.

### Acciones Principales

#### **Conversaciones**

##### `cargarConversaciones(archivadas = false)`
Carga la lista de conversaciones desde el backend.
- **Parámetros**: `archivadas` (boolean) - Si es `true`, carga solo las archivadas
- **Comportamiento**: 
  - Si `archivadas = false`: Reemplaza las conversaciones activas, mantiene las archivadas
  - Si `archivadas = true`: Actualiza solo las archivadas en la lista existente

##### `seleccionarConversacion(id)`
Selecciona una conversación y carga sus mensajes.
- **Parámetros**: `id` (number|string) - ID de la conversación
- **Comportamiento**:
  1. Obtiene los detalles de la conversación
  2. Actualiza la lista de conversaciones
  3. Establece como conversación activa
  4. Limpia mensajes anteriores
  5. Carga los mensajes de la conversación

##### `crearConversacion(nombre, usuarios)`
Crea una nueva conversación.
- **Parámetros**: 
  - `nombre` (string) - Nombre opcional de la conversación
  - `usuarios` (Array<number>) - IDs de los usuarios participantes
- **Comportamiento**: Crea la conversación y la selecciona automáticamente

##### `buscarUsuarios(query)`
Busca usuarios para agregar a conversaciones.
- **Parámetros**: `query` (string) - Texto de búsqueda
- **Retorna**: Array de usuarios encontrados

##### `archivarConversacion(id)`
Archiva una conversación.
- **Comportamiento**: Marca la conversación como archivada y limpia si es la activa

##### `desarchivarConversacion(id)`
Desarchiva una conversación.
- **Comportamiento**: Remueve la marca de archivada

##### `salirConversacion(id)`
Sale de una conversación (grupo).
- **Comportamiento**: Remueve la conversación de la lista y limpia si es la activa

#### **Mensajes**

##### `cargarMensajes(conversacionId, antes = null)`
Carga mensajes de una conversación.
- **Parámetros**:
  - `conversacionId` (number|string) - ID de la conversación
  - `antes` (number|null) - ID del mensaje para paginación hacia atrás
- **Comportamiento**:
  - Si `antes = null`: Primera carga, reemplaza todos los mensajes
  - Si `antes` tiene valor: Agrega mensajes más antiguos al inicio

##### `cargarMasMensajes()`
Carga más mensajes antiguos (paginación hacia atrás).
- **Comportamiento**: Usa el ID del primer mensaje actual para cargar anteriores

##### `enviarMensaje(contenido, tipo = 'texto', respondeA = null)`
Envía un mensaje de texto.
- **Parámetros**:
  - `contenido` (string) - Contenido del mensaje
  - `tipo` (string) - Tipo: 'texto' o 'link'
  - `respondeA` (number|null) - ID del mensaje al que responde
- **Comportamiento**: 
  - Agrega el mensaje a la lista
  - Actualiza el último mensaje en la conversación
  - Resetea contador de no leídos

##### `enviarArchivo(archivo, contenido = null, respondeA = null)`
Envía un archivo adjunto (alias de `subirAdjunto`).
- **Parámetros**:
  - `archivo` (File) - Archivo a subir
  - `contenido` (string|null) - Mensaje adicional opcional
  - `respondeA` (number|null) - ID del mensaje al que responde

##### `editarMensaje(mensajeId, contenido)`
Edita un mensaje existente.
- **Parámetros**:
  - `mensajeId` (number|string) - ID del mensaje
  - `contenido` (string) - Nuevo contenido
- **Comportamiento**: Actualiza el mensaje en la lista

##### `eliminarMensaje(mensajeId)`
Elimina un mensaje (soft delete).
- **Comportamiento**: Marca el mensaje como eliminado y limpia su contenido

##### `subirAdjunto(conversacionId, archivo, contenido = null, respondeA = null)`
Sube un archivo adjunto a una conversación.
- **Comportamiento**: Similar a `enviarMensaje` pero con archivo

#### **Polling (Actualización en Tiempo Real)**

##### `pollingMensajes()`
Polling de nuevos mensajes para la conversación activa.
- **Comportamiento**: 
  - Obtiene mensajes nuevos desde el último mensaje conocido
  - Agrega nuevos mensajes a la lista
  - Actualiza contador de no leídos

##### `iniciarPollingGlobal()`
Inicia el polling global cada 3 segundos.
- **Comportamiento**:
  1. Actualiza la lista de conversaciones
  2. Si hay conversación activa, hace polling de mensajes
- **Nota**: Solo se puede tener un intervalo activo a la vez

##### `detenerPollingGlobal()`
Detiene el polling global.
- **Comportamiento**: Limpia el intervalo activo

##### `limpiar()`
Limpia todo el estado del store.
- **Comportamiento**: Resetea todas las variables de estado y detiene el polling

---

## 🔌 Service de Chat (`services/chat.js`)

### Propósito
Maneja todas las comunicaciones HTTP con el backend Laravel. Proporciona funciones puras que retornan Promises.

### Estructura de Respuestas del Backend

Todas las respuestas del backend siguen el formato:
```javascript
{
  datos: [...],      // Datos principales
  mensaje: "...",    // Mensaje opcional
  meta: {...}       // Metadatos opcionales (paginación, etc.)
}
```

### Funciones de Conversaciones

#### `obtenerConversaciones(archivadas = false)`
- **Endpoint**: `GET /chat/conversaciones?archivadas={boolean}`
- **Retorna**: `Array<Conversacion>`

#### `obtenerConversacion(id)`
- **Endpoint**: `GET /chat/conversaciones/{id}`
- **Retorna**: `Object<Conversacion>`

#### `crearConversacion(datos)`
- **Endpoint**: `POST /chat/conversaciones`
- **Body**: `{ nombre?: string, usuarios: Array<number> }`
- **Requiere**: CSRF Token
- **Retorna**: `Object<Conversacion>`

#### `eliminarConversacion(id)`
- **Endpoint**: `DELETE /chat/conversaciones/{id}`
- **Requiere**: CSRF Token
- **Retorna**: `Object` (confirmación)

#### `archivarConversacion(id)`
- **Endpoint**: `POST /chat/conversaciones/{id}/archivar`
- **Requiere**: CSRF Token
- **Retorna**: `Object` (confirmación)

#### `desarchivarConversacion(id)`
- **Endpoint**: `POST /chat/conversaciones/{id}/desarchivar`
- **Requiere**: CSRF Token
- **Retorna**: `Object` (confirmación)

#### `salirConversacion(id)`
- **Endpoint**: `POST /chat/conversaciones/{id}/salir`
- **Requiere**: CSRF Token
- **Retorna**: `Object` (confirmación)

#### `agregarMiembro(conversacionId, userId)`
- **Endpoint**: `POST /chat/conversaciones/{id}/miembros`
- **Body**: `{ user_id: number }`
- **Requiere**: CSRF Token
- **Retorna**: `Object<Usuario>`

#### `removerMiembro(conversacionId, userId)`
- **Endpoint**: `DELETE /chat/conversaciones/{id}/miembros/{userId}`
- **Requiere**: CSRF Token
- **Retorna**: `Object` (confirmación)

#### `actualizarAdmin(conversacionId, userId, esAdmin)`
- **Endpoint**: `PUT /chat/conversaciones/{id}/admin`
- **Body**: `{ user_id: number, es_admin: boolean }`
- **Requiere**: CSRF Token
- **Retorna**: `Object` (confirmación)

#### `buscarUsuarios(query)`
- **Endpoint**: `GET /chat/usuarios/buscar?q={query}`
- **Retorna**: `Array<Usuario>`

### Funciones de Mensajes

#### `obtenerMensajes(conversacionId, params = {})`
- **Endpoint**: `GET /chat/conversaciones/{id}/mensajes`
- **Query Params**: `{ per_page?: number, antes?: number }`
- **Retorna**: `{ datos: Array<Mensaje>, tiene_mas: boolean }`

#### `pollingMensajes(conversacionId, despues)`
- **Endpoint**: `GET /chat/conversaciones/{id}/mensajes/polling?despues={id}`
- **Retorna**: `{ datos: Array<Mensaje>, nuevos: number }`

#### `enviarMensaje(conversacionId, datos)`
- **Endpoint**: `POST /chat/conversaciones/{id}/mensajes`
- **Body**: `{ contenido: string, tipo?: string, responde_a_id?: number }`
- **Requiere**: CSRF Token
- **Retorna**: `Object<Mensaje>`

#### `editarMensaje(mensajeId, contenido)`
- **Endpoint**: `PUT /chat/mensajes/{id}`
- **Body**: `{ contenido: string }`
- **Requiere**: CSRF Token
- **Retorna**: `Object<Mensaje>`

#### `eliminarMensaje(mensajeId)`
- **Endpoint**: `DELETE /chat/mensajes/{id}`
- **Requiere**: CSRF Token
- **Retorna**: `Object` (confirmación)

#### `buscarMensajes(conversacionId, query)`
- **Endpoint**: `GET /chat/conversaciones/{id}/mensajes/buscar?q={query}`
- **Retorna**: `Array<Mensaje>`

#### `marcarVisto(conversacionId)`
- **Endpoint**: `POST /chat/conversaciones/{id}/visto`
- **Requiere**: CSRF Token
- **Retorna**: `Object` (confirmación)

### Funciones de Adjuntos

#### `subirAdjunto(conversacionId, archivo, datos = {})`
- **Endpoint**: `POST /chat/conversaciones/{id}/adjuntos`
- **Body**: `FormData` con:
  - `archivo`: File
  - `contenido?`: string
  - `responde_a_id?`: number
- **Requiere**: CSRF Token
- **Headers**: `Content-Type: multipart/form-data`
- **Retorna**: `Object<Mensaje>` (con adjunto)

#### `eliminarAdjunto(adjuntoId)`
- **Endpoint**: `DELETE /chat/adjuntos/{id}`
- **Requiere**: CSRF Token
- **Retorna**: `Object` (confirmación)

---

## 🔄 Flujo de Datos

### Ejemplo: Enviar un Mensaje

1. **Componente** (`MensajeInput.vue`):
   ```javascript
   await chatStore.enviarMensaje('Hola mundo', 'texto')
   ```

2. **Store** (`stores/chat.js`):
   ```javascript
   async function enviarMensaje(contenido, tipo, respondeA) {
     const mensaje = await chatService.enviarMensaje(
       conversacionActiva.value.id,
       { contenido, tipo, responde_a_id: respondeA }
     )
     mensajes.value.push(mensaje)
     // Actualizar último mensaje en conversación
   }
   ```

3. **Service** (`services/chat.js`):
   ```javascript
   export async function enviarMensaje(conversacionId, datos) {
     await getCsrfToken()
     const response = await api.post(`/chat/conversaciones/${conversacionId}/mensajes`, datos)
     return response.data.datos
   }
   ```

4. **Backend** (Laravel):
   - Valida con `SolicitudCrearMensaje`
   - Crea el mensaje en la BD
   - Retorna `RecursoMensaje`

5. **Store** actualiza el estado reactivo
6. **Componente** se actualiza automáticamente (reactividad Vue)

---

## ⚙️ Polling Global

El polling global se ejecuta cada **3 segundos** cuando está activo:

1. **Actualiza conversaciones**: Obtiene la lista actualizada
2. **Polling de mensajes**: Si hay conversación activa, busca nuevos mensajes

### Inicio/Detención

- **Inicia**: Cuando se abre el `CajonChat` (drawer)
- **Detiene**: Cuando se cierra el drawer o se desmonta el componente
- **Gestión**: `layoutStore.chatDrawerAbierto` controla el estado

---

## 📝 Notas Importantes

1. **CSRF Token**: Todas las operaciones POST/PUT/DELETE requieren obtener el token CSRF primero usando `getCsrfToken()`

2. **Manejo de Errores**: El store propaga errores, los componentes deben manejarlos con try/catch

3. **Reactividad**: Todos los cambios en el store se reflejan automáticamente en los componentes que lo usan

4. **Limpieza**: Siempre llamar `limpiar()` al desmontar componentes para evitar memory leaks

5. **Polling**: El polling solo debe estar activo cuando el usuario está viendo el chat

---

## 🎯 Uso en Componentes

```javascript
import { useChatStore } from '@/stores/chat'

const chatStore = useChatStore()

// Cargar conversaciones
await chatStore.cargarConversaciones()

// Seleccionar conversación
await chatStore.seleccionarConversacion(1)

// Enviar mensaje
await chatStore.enviarMensaje('Hola', 'texto')

// Acceder a estado reactivo
const conversaciones = chatStore.conversaciones
const activa = chatStore.conversacionActiva
const noLeidos = chatStore.mensajesNoLeidos
```

---

## 📚 Referencias

- **Store**: `frontend/src/stores/chat.js`
- **Service**: `frontend/src/services/chat.js`
- **Backend Routes**: `backend/routes/app/chat.php`
- **Backend Controllers**: `backend/app/Http/Controllers/Chat/`

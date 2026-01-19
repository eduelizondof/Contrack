import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import * as chatService from '@/services/chat'
import { useAuthStore } from '@/stores/auth'

export const useChatStore = defineStore('chat', () => {
  const authStore = useAuthStore()
  // Estado
  const conversaciones = ref([])
  const conversacionActiva = ref(null)
  const mensajes = ref([])
  const cargando = ref(false) // Carga inicial visible
  const actualizandoEnBackground = ref(false) // Polling silencioso
  const cargandoMensajes = ref(false)
  const tieneMasMensajes = ref(false)
  const sidebarAbierto = ref(false)
  const pollingInterval = ref(null)
  const pollingEstadoInterval = ref(null) // Polling ligero cuando no está en chat
  const cargarArchivadas = ref(false) // Controla si se deben cargar las archivadas en el polling
  const totalMensajesNoLeidos = ref(0) // Total para badges

  // Getters
  const conversacionesActivas = computed(() => {
    return conversaciones.value.filter(conv => !conv.archivada)
  })

  const conversacionesArchivadas = computed(() => {
    return conversaciones.value.filter(conv => conv.archivada)
  })

  const mensajesNoLeidos = computed(() => {
    return conversaciones.value.reduce((total, conv) => {
      return total + (conv.mensajes_no_leidos || 0)
    }, 0)
  })

  // ==================== CONVERSACIONES ====================

  /**
   * Cargar conversaciones
   * @param {boolean} archivadas - Si es true, carga solo las archivadas
   * @param {boolean} mostrarLoading - Si es true, muestra loading visible (solo para carga inicial)
   */
  async function cargarConversaciones(archivadas = false, mostrarLoading = false) {
    if (mostrarLoading) {
      cargando.value = true
    } else {
      actualizandoEnBackground.value = true
    }
    
    try {
      const datos = await chatService.obtenerConversaciones(archivadas)
      if (archivadas) {
        // Actualizar solo las archivadas
        datos.forEach(conv => {
          const index = conversaciones.value.findIndex(c => c.id === conv.id)
          if (index >= 0) {
            conversaciones.value[index] = conv
          } else {
            conversaciones.value.push(conv)
          }
        })
      } else {
        // Merge inteligente sin reemplazar todo
        datos.forEach(conv => {
          const index = conversaciones.value.findIndex(c => c.id === conv.id)
          if (index >= 0) {
            // Actualizar existente manteniendo estado local si es necesario
            conversaciones.value[index] = conv
          } else {
            // Solo agregar si no existe
            conversaciones.value.push(conv)
          }
        })
        // Remover conversaciones que ya no existen en el servidor (excepto archivadas)
        const idsServidor = datos.map(c => c.id)
        conversaciones.value = conversaciones.value.filter(c => 
          c.archivada || idsServidor.includes(c.id)
        )
      }
    } catch (error) {
      console.error('Error al cargar conversaciones:', error)
      throw error
    } finally {
      if (mostrarLoading) {
        cargando.value = false
      } else {
        actualizandoEnBackground.value = false
      }
    }
  }

  /**
   * Seleccionar conversación y cargar sus mensajes
   * @param {number|string} id - ID de la conversación
   */
  function seleccionarConversacion(id) {
    // OPTIMIZACIÓN: Usar conversación de la lista inmediatamente (ya está en memoria)
    const conversacionEnLista = conversaciones.value.find(c => c.id === id)
    
    // Validación de seguridad: verificar que la conversación existe y tiene usuarios
    if (!conversacionEnLista) {
      console.warn('Conversación no encontrada en la lista')
      return
    }
    
    // Verificar que la conversación tiene usuarios (validación básica de seguridad)
    if (!conversacionEnLista.usuarios || conversacionEnLista.usuarios.length === 0) {
      console.error('Conversación sin usuarios - posible error de sincronización')
      // Recargar conversaciones en background
      cargarConversaciones(false, false).catch(error => {
        console.error('Error al recargar conversaciones:', error)
      })
      return
    }
    
    if (conversacionEnLista) {
      // Establecer conversación activa inmediatamente (sin esperar API)
      conversacionActiva.value = { ...conversacionEnLista }
      
        // Si tiene último mensaje, mostrarlo temporalmente para carga rápida
        if (conversacionEnLista.ultimo_mensaje) {
          const ultimoMensaje = conversacionEnLista.ultimo_mensaje
          // El usuario viene como objeto {id, name} desde el backend
          const usuarioData = ultimoMensaje.usuario || { id: 0, name: 'Usuario' }
          
          // Determinar si es propio comparando el ID del usuario del mensaje con el usuario autenticado
          const esPropio = authStore.usuario?.id && usuarioData.id && 
                          authStore.usuario.id === usuarioData.id
          
          mensajes.value = [{
            id: ultimoMensaje.id,
            contenido: ultimoMensaje.contenido || '',
            tipo: 'texto',
            creado_el: ultimoMensaje.creado_el,
            usuario: {
              id: usuarioData.id || 0,
              name: usuarioData.name || 'Usuario',
              email: ''
            },
            es_propio: esPropio,
            eliminado: false,
            editado: false,
            vistos_count: 0,
            adjuntos: [],
            responde_a: null,
            es_temporal: true // Flag para identificar mensaje temporal
          }]
        } else {
          mensajes.value = []
        }
      
      tieneMasMensajes.value = false
    } else {
      // Si no está en la lista, limpiar
      conversacionActiva.value = null
      mensajes.value = []
      tieneMasMensajes.value = false
    }

    // Cargar detalles completos y mensajes en background (sin bloquear UI)
    // NO usar await - ejecutar completamente en background
    Promise.all([
      chatService.obtenerConversacion(id).then(conversacion => {
        // Actualizar en la lista
        const index = conversaciones.value.findIndex(c => c.id === conversacion.id)
        if (index >= 0) {
          conversaciones.value[index] = conversacion
        } else {
          conversaciones.value.push(conversacion)
        }
        
        // Actualizar conversación activa con datos completos
        if (conversacionActiva.value?.id === conversacion.id) {
          conversacionActiva.value = conversacion
        }
        
        return conversacion
      }).catch(error => {
        console.error('Error al cargar detalles de conversación:', error)
      }),
      cargarMensajes(id).catch(error => {
        console.error('Error al cargar mensajes:', error)
      })
    ]).catch(error => {
      console.error('Error en carga en background:', error)
      // Si falla, mantener la conversación de la lista
    })
  }

  /**
   * Crear nueva conversación
   * @param {string} nombre - Nombre de la conversación (opcional)
   * @param {Array<number>} usuarios - IDs de los usuarios
   */
  async function crearConversacion(nombre, usuarios) {
    creandoConversacion.value = true
    try {
      const conversacion = await chatService.crearConversacion({ usuarios, nombre })
      
      // Agregar a la lista
      conversaciones.value.unshift(conversacion)
      
      // Seleccionar la nueva conversación
      await seleccionarConversacion(conversacion.id)
      
      return conversacion
    } catch (error) {
      console.error('Error al crear conversación:', error)
      throw error
    } finally {
      creandoConversacion.value = false
    }
  }

  /**
   * Buscar usuarios para agregar a conversación
   * @param {string} query - Texto de búsqueda
   */
  async function buscarUsuarios(query) {
    try {
      return await chatService.buscarUsuarios(query)
    } catch (error) {
      console.error('Error al buscar usuarios:', error)
      throw error
    }
  }

  /**
   * Archivar conversación
   * @param {number|string} id - ID de la conversación
   */
  async function archivarConversacion(id) {
    try {
      await chatService.archivarConversacion(id)
      
      // Actualizar en la lista
      const index = conversaciones.value.findIndex(c => c.id === id)
      if (index >= 0) {
        conversaciones.value[index].archivada = true
      }
      
      // Si es la activa, limpiar
      if (conversacionActiva.value?.id === id) {
        conversacionActiva.value = null
        mensajes.value = []
      }
    } catch (error) {
      console.error('Error al archivar conversación:', error)
      throw error
    }
  }

  /**
   * Desarchivar conversación
   * @param {number|string} id - ID de la conversación
   */
  async function desarchivarConversacion(id) {
    try {
      await chatService.desarchivarConversacion(id)
      
      // Actualizar en la lista
      const index = conversaciones.value.findIndex(c => c.id === id)
      if (index >= 0) {
        conversaciones.value[index].archivada = false
      }
    } catch (error) {
      console.error('Error al desarchivar conversación:', error)
      throw error
    }
  }

  /**
   * Eliminar conversación (solo creador)
   * @param {number|string} id - ID de la conversación
   */
  async function eliminarConversacion(id) {
    try {
      await chatService.eliminarConversacion(id)
      
      // Remover de la lista
      conversaciones.value = conversaciones.value.filter(c => c.id !== id)
      
      // Si es la activa, limpiar
      if (conversacionActiva.value?.id === id) {
        conversacionActiva.value = null
        mensajes.value = []
      }
    } catch (error) {
      console.error('Error al eliminar conversación:', error)
      throw error
    }
  }

  /**
   * Salir de conversación
   * @param {number|string} id - ID de la conversación
   * @returns {Promise<Object>} - Respuesta con flag grupo_eliminado
   */
  async function salirConversacion(id) {
    try {
      const respuesta = await chatService.salirConversacion(id)
      
      // Remover de la lista
      conversaciones.value = conversaciones.value.filter(c => c.id !== id)
      
      // Si es la activa, limpiar
      if (conversacionActiva.value?.id === id) {
        conversacionActiva.value = null
        mensajes.value = []
      }
      
      return respuesta
    } catch (error) {
      console.error('Error al salir de conversación:', error)
      throw error
    }
  }

  // ==================== MENSAJES ====================

  /**
   * Cargar mensajes de una conversación
   * @param {number|string} conversacionId - ID de la conversación
   * @param {number} antes - ID del mensaje para paginación hacia atrás
   */
  async function cargarMensajes(conversacionId, antes = null) {
    if (!conversacionId) return

    cargandoMensajes.value = true
    try {
      const { datos, tiene_mas } = await chatService.obtenerMensajes(conversacionId, {
        per_page: 10,
        antes
      })

      if (antes) {
        // Agregar al inicio (mensajes más antiguos)
        // Filtrar mensajes temporales si existen
        const mensajesSinTemporales = mensajes.value.filter(m => !m.es_temporal)
        mensajes.value = [...datos, ...mensajesSinTemporales]
      } else {
        // Reemplazar (primera carga) - esto reemplazará cualquier mensaje temporal
        mensajes.value = datos
      }

      tieneMasMensajes.value = tiene_mas
    } catch (error) {
      console.error('Error al cargar mensajes:', error)
      throw error
    } finally {
      cargandoMensajes.value = false
    }
  }

  /**
   * Cargar más mensajes (paginación hacia atrás)
   */
  async function cargarMasMensajes() {
    if (!conversacionActiva.value || !tieneMasMensajes.value || cargandoMensajes.value) {
      return
    }

    const primerMensaje = mensajes.value[0]
    if (primerMensaje) {
      await cargarMensajes(conversacionActiva.value.id, primerMensaje.id)
    }
  }

  const enviandoMensaje = ref(false)
  const editandoMensaje = ref(false)
  const creandoConversacion = ref(false)

  /**
   * Enviar mensaje
   * @param {string} contenido - Contenido del mensaje
   * @param {string} tipo - Tipo de mensaje (texto, link)
   * @param {number} respondeA - ID del mensaje al que responde (opcional)
   */
  async function enviarMensaje(contenido, tipo = 'texto', respondeA = null) {
    if (!conversacionActiva.value) return

    enviandoMensaje.value = true
    try {
      const mensaje = await chatService.enviarMensaje(conversacionActiva.value.id, {
        contenido,
        tipo,
        responde_a_id: respondeA
      })

      // Verificar que no existe antes de agregar (evitar duplicados)
      if (!mensajes.value.some(m => m.id === mensaje.id)) {
        mensajes.value.push(mensaje)
      }

      // Actualizar último mensaje en la conversación
      const index = conversaciones.value.findIndex(c => c.id === conversacionActiva.value.id)
      if (index >= 0) {
        conversaciones.value[index].ultimo_mensaje = {
          id: mensaje.id,
          contenido: mensaje.contenido || mensaje.adjuntos?.[0]?.nombre_original || 'Archivo',
          usuario: mensaje.usuario.name,
          creado_el: mensaje.creado_el
        }
        conversaciones.value[index].mensajes_no_leidos = 0
      }

      return mensaje
    } catch (error) {
      console.error('Error al enviar mensaje:', error)
      throw error
    } finally {
      enviandoMensaje.value = false
    }
  }

  /**
   * Enviar archivo (alias para subirAdjunto)
   * @param {File} archivo - Archivo a subir
   * @param {string} contenido - Mensaje adicional (opcional)
   * @param {number} respondeA - ID del mensaje al que responde (opcional)
   */
  async function enviarArchivo(archivo, contenido = null, respondeA = null) {
    if (!conversacionActiva.value) return

    enviandoMensaje.value = true
    try {
      return await subirAdjunto(conversacionActiva.value.id, archivo, contenido, respondeA)
    } finally {
      enviandoMensaje.value = false
    }
  }

  /**
   * Editar mensaje
   * @param {number|string} mensajeId - ID del mensaje
   * @param {string} contenido - Nuevo contenido
   */
  async function editarMensaje(mensajeId, contenido) {
    editandoMensaje.value = true
    try {
      const mensaje = await chatService.editarMensaje(mensajeId, contenido)

      // Actualizar en la lista
      const index = mensajes.value.findIndex(m => m.id === mensajeId)
      if (index >= 0) {
        mensajes.value[index] = mensaje
      }

      return mensaje
    } catch (error) {
      console.error('Error al editar mensaje:', error)
      throw error
    } finally {
      editandoMensaje.value = false
    }
  }

  /**
   * Eliminar mensaje
   * @param {number|string} mensajeId - ID del mensaje
   */
  async function eliminarMensaje(mensajeId) {
    try {
      await chatService.eliminarMensaje(mensajeId)

      // Actualizar en la lista
      const index = mensajes.value.findIndex(m => m.id === mensajeId)
      if (index >= 0) {
        mensajes.value[index].eliminado = true
        mensajes.value[index].contenido = null
      }
    } catch (error) {
      console.error('Error al eliminar mensaje:', error)
      throw error
    }
  }

  /**
   * Buscar mensajes en una conversación
   * @param {number|string} conversacionId - ID de la conversación
   * @param {string} query - Texto de búsqueda
   * @returns {Promise<Array>} - Lista de mensajes encontrados
   */
  async function buscarMensajes(conversacionId, query) {
    try {
      return await chatService.buscarMensajes(conversacionId, query)
    } catch (error) {
      console.error('Error al buscar mensajes:', error)
      throw error
    }
  }

  /**
   * Cargar todos los mensajes desde el inicio hasta un mensaje específico
   * @param {number|string} mensajeId - ID del mensaje hasta el cual cargar
   * @returns {Promise<Object|null>} - El mensaje encontrado o null
   */
  async function cargarMensajesHasta(mensajeId) {
    if (!conversacionActiva.value) {
      console.error('No hay conversación activa')
      return null
    }

    // Verificar si el mensaje ya está cargado
    const mensajeExistente = mensajes.value.find(m => m.id === mensajeId)
    if (mensajeExistente) {
      return mensajeExistente
    }

    cargandoMensajes.value = true
    try {
      const mensajesCargados = []
      let tieneMas = true
      let ultimoId = null
      const maxIntentos = 100 // Límite de seguridad para evitar loops infinitos
      let intentos = 0

      // Cargar mensajes hacia atrás hasta encontrar el mensaje o llegar al inicio
      while (tieneMas && intentos < maxIntentos) {
        intentos++
        
        const { datos, tiene_mas } = await chatService.obtenerMensajes(
          conversacionActiva.value.id,
          {
            per_page: 10,
            antes: ultimoId
          }
        )

        // Verificar si encontramos el mensaje en esta carga
        const mensajeEncontrado = datos.find(m => m.id === mensajeId)
        if (mensajeEncontrado) {
          // Agregar todos los mensajes hasta el encontrado (incluyéndolo)
          const indice = datos.findIndex(m => m.id === mensajeId)
          mensajesCargados.push(...datos.slice(0, indice + 1))
          
          // Combinar con mensajes existentes (evitar duplicados)
          const mensajesSinDuplicados = [
            ...mensajesCargados,
            ...mensajes.value.filter(m => !mensajesCargados.some(c => c.id === m.id))
          ]
          
          // Ordenar por fecha de creación (más antiguos primero)
          mensajes.value = mensajesSinDuplicados.sort((a, b) => {
            return new Date(a.creado_el) - new Date(b.creado_el)
          })
          
          tieneMasMensajes.value = false // Ya cargamos todo hasta este punto
          return mensajeEncontrado
        }

        // Si no encontramos el mensaje, agregar estos mensajes y continuar
        mensajesCargados.push(...datos)
        
        if (datos.length > 0) {
          ultimoId = datos[0].id // ID del mensaje más antiguo de esta carga
        }
        
        tieneMas = tiene_mas
      }

      // Si llegamos aquí, no encontramos el mensaje
      console.warn('Mensaje no encontrado después de cargar mensajes')
      return null
    } catch (error) {
      console.error('Error al cargar mensajes hasta:', error)
      throw error
    } finally {
      cargandoMensajes.value = false
    }
  }

  /**
   * Subir adjunto
   * @param {number|string} conversacionId - ID de la conversación
   * @param {File} archivo - Archivo a subir
   * @param {string} contenido - Mensaje adicional (opcional)
   * @param {number} respondeA - ID del mensaje al que responde (opcional)
   */
  async function subirAdjunto(conversacionId, archivo, contenido = null, respondeA = null) {
    try {
      const mensaje = await chatService.subirAdjunto(conversacionId, archivo, {
        contenido,
        responde_a_id: respondeA
      })

      // Verificar que no existe antes de agregar (evitar duplicados)
      if (!mensajes.value.some(m => m.id === mensaje.id)) {
        mensajes.value.push(mensaje)
      }

      // Actualizar último mensaje en la conversación
      const index = conversaciones.value.findIndex(c => c.id === conversacionId)
      if (index >= 0) {
        conversaciones.value[index].ultimo_mensaje = {
          id: mensaje.id,
          contenido: mensaje.adjuntos?.[0]?.nombre_original || 'Archivo',
          usuario: mensaje.usuario.name,
          creado_el: mensaje.creado_el
        }
        conversaciones.value[index].mensajes_no_leidos = 0
      }

      return mensaje
    } catch (error) {
      console.error('Error al subir adjunto:', error)
      throw error
    }
  }

  // ==================== POLLING ====================

  /**
   * Polling de mensajes para la conversación activa
   */
  async function pollingMensajes() {
    if (!conversacionActiva.value || mensajes.value.length === 0) {
      return
    }

    try {
      const ultimoMensaje = mensajes.value[mensajes.value.length - 1]
      const { datos, nuevos } = await chatService.pollingMensajes(
        conversacionActiva.value.id,
        ultimoMensaje.id
      )

      if (nuevos > 0) {
        // Filtrar duplicados antes de agregar
        const nuevosSinDuplicados = datos.filter(m => 
          !mensajes.value.some(existing => existing.id === m.id)
        )
        
        if (nuevosSinDuplicados.length > 0) {
          mensajes.value.push(...nuevosSinDuplicados)
        }

        // Actualizar contador de no leídos
        const index = conversaciones.value.findIndex(c => c.id === conversacionActiva.value.id)
        if (index >= 0) {
          conversaciones.value[index].mensajes_no_leidos = 0
        }
      }
    } catch (error) {
      console.error('Error en polling de mensajes:', error)
    }
  }

  /**
   * Polling ligero de estado (solo total de mensajes no leídos)
   * Para usar cuando el usuario NO está en el chat
   */
  async function pollingEstado() {
    try {
      const { total_no_leidos } = await chatService.obtenerEstado()
      totalMensajesNoLeidos.value = total_no_leidos
    } catch (error) {
      console.error('Error en polling de estado:', error)
    }
  }

  /**
   * Polling completo de conversaciones (sin loading visible)
   * Para usar cuando el usuario está en el chat
   */
  async function pollingConversaciones() {
    actualizandoEnBackground.value = true
    try {
      await cargarConversaciones(false, false) // Sin loading visible
      
      // Cargar archivadas solo si el usuario las está viendo
      if (cargarArchivadas.value) {
        await cargarConversaciones(true, false)
      }

      // Polling de mensajes si hay conversación activa
      if (conversacionActiva.value) {
        await pollingMensajes()
      }
    } catch (error) {
      console.error('Error en polling de conversaciones:', error)
    } finally {
      actualizandoEnBackground.value = false
    }
  }

  /**
   * Iniciar polling completo (cuando el usuario está en el chat)
   * Polling cada 5 segundos de conversaciones y mensajes
   */
  function iniciarPollingCompleto() {
    if (pollingInterval.value) {
      return // Ya está iniciado
    }

    // Polling cada 5 segundos
    pollingInterval.value = setInterval(async () => {
      await pollingConversaciones()
    }, 5000)
  }

  /**
   * Iniciar polling ligero (cuando el usuario NO está en el chat)
   * Polling cada 30 segundos solo del estado (total no leídos)
   */
  function iniciarPollingLigero() {
    if (pollingEstadoInterval.value) {
      return // Ya está iniciado
    }

    // Polling cada 30 segundos
    pollingEstadoInterval.value = setInterval(async () => {
      await pollingEstado()
    }, 30000)
  }

  /**
   * Detener polling completo
   */
  function detenerPollingCompleto() {
    if (pollingInterval.value) {
      clearInterval(pollingInterval.value)
      pollingInterval.value = null
    }
  }

  /**
   * Detener polling ligero
   */
  function detenerPollingLigero() {
    if (pollingEstadoInterval.value) {
      clearInterval(pollingEstadoInterval.value)
      pollingEstadoInterval.value = null
    }
  }

  /**
   * Iniciar polling global (compatibilidad con código existente)
   * Ahora inicia el polling completo
   */
  function iniciarPollingGlobal() {
    iniciarPollingCompleto()
  }

  /**
   * Detener polling global (compatibilidad con código existente)
   */
  function detenerPollingGlobal() {
    detenerPollingCompleto()
  }

  /**
   * Limpiar estado del store
   */
  function limpiar() {
    conversaciones.value = []
    conversacionActiva.value = null
    mensajes.value = []
    tieneMasMensajes.value = false
    detenerPollingCompleto()
    detenerPollingLigero()
  }

  /**
   * Activar/desactivar carga de archivadas en polling
   * @param {boolean} activar - Si es true, activa la carga de archivadas
   */
  function toggleCargarArchivadas(activar) {
    cargarArchivadas.value = activar
    // Si se activa, cargar inmediatamente
    if (activar) {
      cargarConversaciones(true).catch(error => {
        console.error('Error al cargar archivadas:', error)
      })
    }
  }

  /**
   * Toggle sidebar (para móvil)
   */
  function toggleSidebar() {
    sidebarAbierto.value = !sidebarAbierto.value
  }

  /**
   * Agregar miembro a conversación
   * @param {number|string} conversacionId - ID de la conversación
   * @param {number|string} userId - ID del usuario
   */
  async function agregarMiembro(conversacionId, userId) {
    try {
      const nuevoMiembro = await chatService.agregarMiembro(conversacionId, userId)
      
      // Actualizar conversación activa
      if (conversacionActiva.value?.id === conversacionId) {
        const conversacion = await chatService.obtenerConversacion(conversacionId)
        conversacionActiva.value = conversacion
        
        // Actualizar en la lista
        const index = conversaciones.value.findIndex(c => c.id === conversacionId)
        if (index >= 0) {
          conversaciones.value[index] = conversacion
        }
      }
      
      return nuevoMiembro
    } catch (error) {
      console.error('Error al agregar miembro:', error)
      throw error
    }
  }

  /**
   * Remover miembro de conversación
   * @param {number|string} conversacionId - ID de la conversación
   * @param {number|string} userId - ID del usuario
   */
  async function removerMiembro(conversacionId, userId) {
    try {
      await chatService.removerMiembro(conversacionId, userId)
      
      // Actualizar conversación activa
      if (conversacionActiva.value?.id === conversacionId) {
        const conversacion = await chatService.obtenerConversacion(conversacionId)
        conversacionActiva.value = conversacion
        
        // Actualizar en la lista
        const index = conversaciones.value.findIndex(c => c.id === conversacionId)
        if (index >= 0) {
          conversaciones.value[index] = conversacion
        }
      }
    } catch (error) {
      console.error('Error al remover miembro:', error)
      throw error
    }
  }

  /**
   * Cambiar rol de admin
   * @param {number|string} conversacionId - ID de la conversación
   * @param {number|string} userId - ID del usuario
   * @param {boolean} esAdmin - Si es admin o no
   */
  async function cambiarAdmin(conversacionId, userId, esAdmin) {
    try {
      await chatService.actualizarAdmin(conversacionId, userId, esAdmin)
      
      // Actualizar conversación activa
      if (conversacionActiva.value?.id === conversacionId) {
        const conversacion = await chatService.obtenerConversacion(conversacionId)
        conversacionActiva.value = conversacion
        
        // Actualizar en la lista
        const index = conversaciones.value.findIndex(c => c.id === conversacionId)
        if (index >= 0) {
          conversaciones.value[index] = conversacion
        }
      }
    } catch (error) {
      console.error('Error al cambiar admin:', error)
      throw error
    }
  }

  return {
    // Estado
    conversaciones,
    conversacionActiva,
    mensajes,
    cargando,
    actualizandoEnBackground,
    cargandoMensajes,
    tieneMasMensajes,
    sidebarAbierto,
    enviandoMensaje,
    editandoMensaje,
    creandoConversacion,
    cargarArchivadas,
    totalMensajesNoLeidos,
    // Getters
    conversacionesActivas,
    conversacionesArchivadas,
    mensajesNoLeidos,
    // Acciones
    cargarConversaciones,
    seleccionarConversacion,
    crearConversacion,
    buscarUsuarios,
    archivarConversacion,
    desarchivarConversacion,
    eliminarConversacion,
    salirConversacion,
    cargarMensajes,
    cargarMasMensajes,
    enviarMensaje,
    enviarArchivo,
    editarMensaje,
    eliminarMensaje,
    buscarMensajes,
    cargarMensajesHasta,
    subirAdjunto,
    pollingMensajes,
    pollingEstado,
    pollingConversaciones,
    iniciarPollingGlobal,
    detenerPollingGlobal,
    iniciarPollingCompleto,
    detenerPollingCompleto,
    iniciarPollingLigero,
    detenerPollingLigero,
    toggleCargarArchivadas,
    toggleSidebar,
    agregarMiembro,
    removerMiembro,
    cambiarAdmin,
    limpiar,
  }
})

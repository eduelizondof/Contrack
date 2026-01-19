import { ref } from 'vue'

/**
 * Composable para manejo de formularios con estado reactivo
 * Útil para formularios de crear/editar productos, proyectos, clientes, etc.
 * 
 * @example
 * import { useForm } from '@/composables/useForm'
 * 
 * const { form, errores, cargando, reset, setError, limpiarErrores } = useForm({
 *   email: '',
 *   password: ''
 * })
 * 
 * function handleSubmit() {
 *   limpiarErrores()
 *   // validar y enviar
 * }
 */
export function useForm(initialValues = {}) {
  const form = ref({ ...initialValues })
  const errores = ref({})
  const cargando = ref(false)

  function reset() {
    form.value = { ...initialValues }
    errores.value = {}
  }

  function setError(campo, mensaje) {
    errores.value[campo] = mensaje
  }

  function limpiarErrores() {
    errores.value = {}
  }

  return { form, errores, cargando, reset, setError, limpiarErrores }
}

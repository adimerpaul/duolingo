<template>
  <q-page class="q-pa-md">
    <q-card flat bordered class="q-mb-md">
      <q-card-section class="row items-center">
        <div>
          <div class="text-h6 text-title">Personajes</div>
          <div class="text-caption text-grey-7">
            Gestión de personajes y sus estilos de imagen.
          </div>
        </div>
        <q-space />
        <q-input v-model="filter" label="Buscar" dense outlined debounce="300" style="width: 280px">
          <template v-slot:append><q-icon name="search" /></template>
        </q-input>
      </q-card-section>
    </q-card>

    <q-table
      :rows="personajes"
      :columns="columns"
      row-key="id"
      dense
      flat
      bordered
      wrap-cells
      :filter="filter"
      :rows-per-page-options="[0]"
      loading-label="Cargando..."
      no-data-label="Sin registros"
      :loading="loading"
    >
      <template v-slot:top-right>
        <q-btn
          color="positive"
          label="Nuevo"
          no-caps
          icon="add_circle_outline"
          :loading="loading"
          class="q-mr-sm"
          @click="personajeNew"
        />
        <q-btn
          color="primary"
          label="Actualizar"
          no-caps
          icon="refresh"
          :loading="loading"
          @click="personajesGet"
        />
      </template>

      <template v-slot:body-cell-actions="props">
        <q-td :props="props" class="text-center">
          <q-btn-dropdown label="Opciones" no-caps size="10px" dense color="primary">
            <q-list>
              <q-item clickable v-close-popup @click="personajeEdit(props.row)">
                <q-item-section avatar><q-icon name="edit" /></q-item-section>
                <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
              </q-item>
              <q-separator />
              <q-item clickable v-close-popup @click="personajeDelete(props.row.id)">
                <q-item-section avatar><q-icon name="delete" /></q-item-section>
                <q-item-section><q-item-label>Eliminar</q-item-label></q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>

      <template v-slot:body-cell-estilos="props">
        <q-td :props="props">
          <div class="styles-grid">
            <div v-for="style in estilos" :key="style.key" class="style-card">
              <q-img
                v-if="props.row[style.key]"
                :src="imgStyle(props.row[style.key])"
                class="style-thumb"
                fit="cover"
              />
              <div v-else class="style-thumb style-thumb-empty row items-center justify-center">
                <q-icon name="image_not_supported" color="grey-6" size="22px" />
              </div>
              <div class="style-label">{{ style.label }}</div>
            </div>
          </div>
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialog" persistent>
      <q-card style="width: 860px; max-width: 95vw">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle1 text-weight-bold">
            {{ form.id ? 'Editar personaje' : 'Nuevo personaje' }}
          </div>
          <q-space />
          <q-btn icon="close" flat round dense @click="closeDialog" />
        </q-card-section>

        <q-card-section class="q-pt-sm">
          <q-form @submit.prevent="form.id ? personajePut() : personajePost()">
            <q-input
              v-model="form.nombre"
              label="Nombre"
              dense
              outlined
              :rules="[req]"
              class="q-mb-md"
            />

            <div class="row q-col-gutter-md">
              <div v-for="style in estilos" :key="style.key" class="col-12 col-sm-6 col-md-3">
                <q-file
                  :model-value="form.files[style.key]"
                  @update:model-value="setStyleFile(style.key, $event)"
                  :label="style.label"
                  dense
                  outlined
                  accept="image/*"
                  clearable
                >
                  <template v-slot:prepend>
                    <q-icon name="image" />
                  </template>
                </q-file>

                <div class="q-mt-xs preview-box">
                  <q-img
                    v-if="previewForStyle(style.key)"
                    :src="previewForStyle(style.key)"
                    class="style-thumb"
                    fit="cover"
                  />
                  <div v-else class="style-thumb style-thumb-empty row items-center justify-center">
                    <q-icon name="image_not_supported" color="grey-6" size="22px" />
                  </div>
                </div>
              </div>
            </div>

            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn color="negative" label="Cancelar" no-caps flat @click="closeDialog" :disable="loading" />
              <q-btn color="primary" label="Guardar" no-caps type="submit" :loading="loading" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'PersonajesPage',
  data () {
    return {
      personajes: [],
      loading: false,
      filter: '',
      dialog: false,
      previewUrls: {},
      form: this.emptyForm(),
      estilos: [
        { key: 'estilo_alegre', label: 'Alegre' },
        { key: 'estilo_pensando', label: 'Pensando' },
        { key: 'estilo_confundido', label: 'Confundido' },
        { key: 'estilo_celebrando', label: 'Celebrando' },
        { key: 'estilo_triste', label: 'Triste' },
        { key: 'estilo_motivado', label: 'Motivado' },
        { key: 'estilo_cansado', label: 'Cansado' },
        { key: 'estilo_tierno', label: 'Tierno' }
      ],
      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'nombre', label: 'Nombre', align: 'left', field: 'nombre' },
        {
          name: 'estilos',
          label: 'Estilos',
          align: 'left',
          field: row => row.id
        }
      ]
    }
  },
  mounted () {
    this.personajesGet()
  },
  beforeUnmount () {
    this.clearPreviewUrls()
  },
  methods: {
    emptyForm () {
      return {
        id: null,
        nombre: '',
        estilo_alegre: null,
        estilo_pensando: null,
        estilo_confundido: null,
        estilo_celebrando: null,
        estilo_triste: null,
        estilo_motivado: null,
        estilo_cansado: null,
        estilo_tierno: null,
        files: {
          estilo_alegre: null,
          estilo_pensando: null,
          estilo_confundido: null,
          estilo_celebrando: null,
          estilo_triste: null,
          estilo_motivado: null,
          estilo_cansado: null,
          estilo_tierno: null
        }
      }
    },
    req (v) {
      return !!v || 'Campo requerido'
    },
    imgStyle (filename) {
      return `${this.$url}../../images/${filename}`
    },
    previewForStyle (key) {
      if (this.previewUrls[key]) return this.previewUrls[key]
      if (this.form[key]) return this.imgStyle(this.form[key])
      return null
    },
    setStyleFile (key, file) {
      if (this.previewUrls[key]) {
        URL.revokeObjectURL(this.previewUrls[key])
        delete this.previewUrls[key]
      }

      if (file instanceof File) {
        this.previewUrls[key] = URL.createObjectURL(file)
      }

      this.form.files[key] = file || null
    },
    clearPreviewUrls () {
      Object.values(this.previewUrls).forEach(url => URL.revokeObjectURL(url))
      this.previewUrls = {}
    },
    closeDialog () {
      this.clearPreviewUrls()
      this.dialog = false
    },
    personajeNew () {
      this.clearPreviewUrls()
      this.form = this.emptyForm()
      this.dialog = true
    },
    personajeEdit (row) {
      this.clearPreviewUrls()
      this.form = {
        ...this.emptyForm(),
        ...row,
        files: {
          estilo_alegre: null,
          estilo_pensando: null,
          estilo_confundido: null,
          estilo_celebrando: null,
          estilo_triste: null,
          estilo_motivado: null,
          estilo_cansado: null,
          estilo_tierno: null
        }
      }
      this.dialog = true
    },
    personajesGet () {
      this.loading = true
      this.$axios.get('personajes')
        .then(res => { this.personajes = res.data })
        .catch(e => this.$alert.error(e.response?.data?.message || 'Error cargando personajes'))
        .finally(() => { this.loading = false })
    },
    buildFormData (forUpdate = false) {
      const formData = new FormData()
      formData.append('nombre', this.form.nombre)
      if (forUpdate) formData.append('_method', 'PUT')

      this.estilos.forEach(style => {
        const file = this.form.files[style.key]
        if (file) formData.append(style.key, file)
      })

      return formData
    },
    personajePost () {
      this.loading = true
      this.$axios.post('personajes', this.buildFormData(), {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
        .then(() => {
          this.closeDialog()
          this.$alert.success('Personaje creado')
          this.personajesGet()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo crear'))
        .finally(() => { this.loading = false })
    },
    personajePut () {
      this.loading = true
      this.$axios.post(`personajes/${this.form.id}`, this.buildFormData(true), {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
        .then(() => {
          this.closeDialog()
          this.$alert.success('Personaje actualizado')
          this.personajesGet()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo actualizar'))
        .finally(() => { this.loading = false })
    },
    personajeDelete (id) {
      this.$alert.dialog('¿Desea eliminar el personaje?')
        .onOk(() => {
          this.loading = true
          this.$axios.delete(`personajes/${id}`)
            .then(() => {
              this.$alert.success('Personaje eliminado')
              this.personajesGet()
            })
            .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo eliminar'))
            .finally(() => { this.loading = false })
        })
    }
  }
}
</script>

<style scoped>
.styles-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(90px, 1fr));
  gap: 8px;
  min-width: 380px;
}
.style-card {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.style-thumb {
  width: 100%;
  height: 80px;
  border-radius: 8px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  background: #f6f7f9;
}
.style-thumb-empty {
  background: #f6f7f9;
}
.style-label {
  font-size: 11px;
  line-height: 1.2;
  color: #616161;
}
.preview-box {
  width: 100%;
}
@media (max-width: 900px) {
  .styles-grid {
    grid-template-columns: repeat(2, minmax(90px, 1fr));
    min-width: 220px;
  }
}
</style>

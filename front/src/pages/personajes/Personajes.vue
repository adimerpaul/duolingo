<template>
  <q-page class="q-pa-md">
    <q-card flat bordered class="q-mb-md">
      <q-card-section class="row items-center">
        <div>
          <div class="text-h6 text-title">Personajes</div>
          <div class="text-caption text-grey-7">
            Gestion de personajes y sus estilos de imagen.
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
              <q-item clickable v-close-popup @click="openAllStylesPreview(props.row)">
                <q-item-section avatar><q-icon name="collections" /></q-item-section>
                <q-item-section><q-item-label>Ver todas las imagenes</q-item-label></q-item-section>
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
      <q-card style="width: 900px; max-width: 95vw">
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
                <div class="preview-box">
                  <q-img
                    v-if="previewForStyle(style.key)"
                    :src="previewForStyle(style.key)"
                    class="style-thumb style-thumb-lg"
                    fit="cover"
                  />
                  <div v-else class="style-thumb style-thumb-lg style-thumb-empty row items-center justify-center">
                    <q-icon name="image_not_supported" color="grey-6" size="22px" />
                  </div>
                  <q-btn
                    class="style-menu-btn"
                    color="primary"
                    icon="settings"
                    round
                    dense
                    size="sm"
                  >
                    <q-menu>
                      <q-list style="min-width: 170px">
                        <q-item clickable v-close-popup :disable="!previewForStyle(style.key)" @click="openStylePreview(style.key)">
                          <q-item-section avatar><q-icon name="visibility" /></q-item-section>
                          <q-item-section>Ver imagen</q-item-section>
                        </q-item>
                        <q-item clickable v-close-popup @click="openStylePicker(style.key)">
                          <q-item-section avatar><q-icon name="photo_camera" /></q-item-section>
                          <q-item-section>Cambiar imagen</q-item-section>
                        </q-item>
                        <q-item clickable v-close-popup :disable="!previewForStyle(style.key)" @click="clearStyle(style.key)">
                          <q-item-section avatar><q-icon name="delete" /></q-item-section>
                          <q-item-section>Quitar imagen</q-item-section>
                        </q-item>
                      </q-list>
                    </q-menu>
                    <q-tooltip>Opciones de {{ style.label }}</q-tooltip>
                  </q-btn>
                </div>
                <div class="style-label q-mt-xs text-center">{{ style.label }}</div>
              </div>
            </div>

            <input
              ref="styleFileInput"
              type="file"
              accept="image/*"
              class="hidden-file-input"
              @change="onStyleFileSelected"
            >

            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn color="negative" label="Cancelar" no-caps flat @click="closeDialog" :disable="loading" />
              <q-btn color="primary" label="Guardar" no-caps type="submit" :loading="loading" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="cropDialog" persistent>
      <q-card style="width: 900px; max-width: 96vw">
        <q-card-section class="row items-center q-pb-none">
          <div class="column">
            <div class="text-subtitle1 text-weight-bold">
              Recortar imagen - {{ currentCropStyleLabel || 'Sin estado' }}
            </div>
            <div v-if="cropReady" class="row items-center q-gutter-sm q-mt-xs">
              <div class="text-caption text-grey-7">Tamano alto (px)</div>
              <q-input
                v-model.number="cropSizeInput"
                type="number"
                dense
                outlined
                min="1"
                :max="cropNaturalMaxHeight"
                style="width: 120px"
              />
              <div class="text-caption text-grey-7">Max: {{ cropNaturalMaxHeight }}</div>
              <div class="text-caption text-grey-7">Proporcion fija 3:4</div>
            </div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense @click="cancelCropDialog" />
        </q-card-section>

        <q-card-section>
          <div class="text-caption text-grey-8 q-mb-sm">
            Arrastra el cuadro para moverlo. Usa la esquina inferior derecha del cuadro para hacerlo mas grande o mas pequeno.
          </div>

          <div
            ref="cropContainer"
            class="crop-container"
            @pointerdown="onCropContainerPointerDown"
            @pointermove="onCropPointerMove"
            @pointerup="stopCropDragging"
            @pointercancel="stopCropDragging"
            @pointerleave="stopCropDragging"
          >
            <img
              v-if="cropSourceUrl"
              ref="cropImage"
              :src="cropSourceUrl"
              class="crop-image"
              :style="cropImageStyle"
              @load="onCropImageLoad"
              alt="Imagen para recorte"
            >
            <div
              v-if="cropReady"
              class="crop-box"
              :style="cropBoxStyle"
              @pointerdown.prevent="startCropDragging"
            >
              <div class="crop-grid" />
              <div class="crop-box-handle" @pointerdown.stop.prevent="startCropResizing" />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" color="negative" @click="cancelCropDialog" />
          <q-btn
            no-caps
            label="Usar este recorte"
            color="primary"
            :disable="!cropReady"
            @click="applyCrop"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="previewDialog">
      <q-card style="width: 620px; max-width: 95vw">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle1 text-weight-bold">
            {{ previewDialogTitle || 'Vista de imagen' }}
          </div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section>
          <q-img
            v-if="previewDialogSrc"
            :src="previewDialogSrc"
            fit="contain"
            style="height: 60vh; max-height: 520px"
            class="rounded-borders preview-dialog-image"
          />
          <div v-else class="text-grey-7">Sin imagen disponible.</div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="allStylesDialog">
      <q-card style="width: 980px; max-width: 96vw">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle1 text-weight-bold">
            {{ allStylesDialogTitle }}
          </div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section>
          <div class="all-styles-grid">
            <div v-for="style in estilos" :key="`all-${style.key}`" class="all-style-card">
              <div class="all-style-box">
                <q-img
                  v-if="allStyleImageSrc(style.key)"
                  :src="allStyleImageSrc(style.key)"
                  class="all-style-image"
                  fit="contain"
                />
                <div v-else class="all-style-image all-style-empty row items-center justify-center">
                  <q-icon name="image_not_supported" color="grey-6" size="26px" />
                </div>
                <q-btn
                  class="all-style-menu-btn"
                  color="primary"
                  icon="settings"
                  round
                  dense
                  size="sm"
                >
                  <q-menu>
                    <q-list style="min-width: 170px">
                      <q-item clickable v-close-popup :disable="!allStyleImageSrc(style.key)" @click="openAllStylePreview(style.key)">
                        <q-item-section avatar><q-icon name="visibility" /></q-item-section>
                        <q-item-section>Ver imagen</q-item-section>
                      </q-item>
                      <q-item clickable v-close-popup @click="changeAllStyleImage(style.key)">
                        <q-item-section avatar><q-icon name="photo_camera" /></q-item-section>
                        <q-item-section>Cambiar imagen</q-item-section>
                      </q-item>
                      <q-item clickable v-close-popup :disable="!allStyleImageSrc(style.key)" @click="removeAllStyleImage(style.key)">
                        <q-item-section avatar><q-icon name="delete" /></q-item-section>
                        <q-item-section>Quitar imagen</q-item-section>
                      </q-item>
                    </q-list>
                  </q-menu>
                  <q-tooltip>Opciones de {{ style.label }}</q-tooltip>
                </q-btn>
              </div>
              <div class="style-label text-center q-mt-xs">{{ style.label }}</div>
            </div>
          </div>
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
      pendingStyleKey: null,
      cropDialog: false,
      cropTargetStyleKey: null,
      cropSourceUrl: null,
      cropImageNaturalWidth: 0,
      cropImageNaturalHeight: 0,
      cropImageRect: { x: 0, y: 0, width: 0, height: 0 },
      cropBox: { x: 0, y: 0, width: 0, height: 0 },
      cropSelectionNaturalWidth: 0,
      cropSelectionNaturalHeight: 0,
      cropOutputHeight: 1024,
      cropDisplayHeight: 260,
      cropAspectWidth: 3,
      cropAspectHeight: 4,
      cropDragging: false,
      cropResizing: false,
      cropDragOffset: { x: 0, y: 0 },
      cropResizeStart: { x: 0, y: 0, height: 0 },
      previewDialog: false,
      previewDialogSrc: null,
      previewDialogTitle: '',
      allStylesDialog: false,
      allStylesCurrentRow: null,
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
  computed: {
    cropReady () {
      return this.cropSourceUrl && this.cropImageRect.width > 0 && this.cropBox.width > 0 && this.cropBox.height > 0
    },
    cropImageStyle () {
      return {
        left: `${this.cropImageRect.x}px`,
        top: `${this.cropImageRect.y}px`,
        width: `${this.cropImageRect.width}px`,
        height: `${this.cropImageRect.height}px`
      }
    },
    cropBoxStyle () {
      return {
        left: `${this.cropBox.x}px`,
        top: `${this.cropBox.y}px`,
        width: `${this.cropBox.width}px`,
        height: `${this.cropBox.height}px`
      }
    },
    cropAspectRatio () {
      return this.cropAspectWidth / this.cropAspectHeight
    },
    currentCropStyleLabel () {
      const found = this.estilos.find(s => s.key === this.cropTargetStyleKey)
      return found ? found.label : ''
    },
    cropMinHeight () {
      if (!this.cropReady) return 40
      return Math.min(80, this.cropMaxHeight)
    },
    cropMaxHeight () {
      if (!this.cropReady) return 0
      return Math.min(this.cropImageRect.height, this.cropImageRect.width / this.cropAspectRatio)
    },
    cropNaturalMaxHeight () {
      if (!this.cropImageNaturalWidth || !this.cropImageNaturalHeight) return 0
      return Math.round(Math.min(this.cropImageNaturalHeight, this.cropImageNaturalWidth / this.cropAspectRatio))
    },
    cropSizeInput: {
      get () {
        return Math.max(0, Math.round(this.cropSelectionNaturalHeight || 0))
      },
      set (value) {
        this.setCropSizeFromInput(value)
      }
    },
    allStylesDialogTitle () {
      const nombre = this.allStylesCurrentRow?.nombre || 'Personaje'
      return `Todas las imagenes - ${nombre}`
    }
  },
  mounted () {
    this.personajesGet()
    window.addEventListener('resize', this.handleCropResize)
  },
  beforeUnmount () {
    window.removeEventListener('resize', this.handleCropResize)
    this.clearPreviewUrls()
    this.clearCropSourceUrl()
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
      if (!file) this.form[key] = null
    },
    clearStyle (key) {
      this.setStyleFile(key, null)
    },
    openStylePreview (key) {
      const src = this.previewForStyle(key)
      if (!src) return
      const found = this.estilos.find(s => s.key === key)
      this.previewDialogSrc = src
      this.previewDialogTitle = found ? found.label : 'Vista de imagen'
      this.previewDialog = true
    },
    openAllStylesPreview (row) {
      this.allStylesCurrentRow = row
      this.allStylesDialog = true
    },
    allStyleImageSrc (styleKey) {
      if (!this.allStylesCurrentRow) return null
      const filename = this.allStylesCurrentRow[styleKey]
      return filename ? this.imgStyle(filename) : null
    },
    openAllStylePreview (styleKey) {
      const src = this.allStyleImageSrc(styleKey)
      if (!src) return
      const found = this.estilos.find(s => s.key === styleKey)
      this.previewDialogSrc = src
      this.previewDialogTitle = found ? found.label : 'Vista de imagen'
      this.previewDialog = true
    },
    changeAllStyleImage (styleKey) {
      if (!this.allStylesCurrentRow) return
      const row = this.allStylesCurrentRow
      this.allStylesDialog = false
      this.personajeEdit(row)
      this.$nextTick(() => this.openStylePicker(styleKey))
    },
    removeAllStyleImage (styleKey) {
      if (!this.allStylesCurrentRow) return
      const row = this.allStylesCurrentRow
      this.$alert.dialog('Desea quitar esta imagen?')
        .onOk(() => {
          this.allStylesDialog = false
          this.personajeEdit(row)
          this.$nextTick(() => this.clearStyle(styleKey))
        })
    },
    clearPreviewUrls () {
      Object.values(this.previewUrls).forEach(url => URL.revokeObjectURL(url))
      this.previewUrls = {}
    },
    clearCropSourceUrl () {
      if (this.cropSourceUrl && this.cropSourceUrl.startsWith('blob:')) {
        URL.revokeObjectURL(this.cropSourceUrl)
      }
      this.cropSourceUrl = null
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
        const filename = this.form[style.key]
        if (file) {
          formData.append(style.key, file)
        } else if (filename) {
          formData.append(style.key, filename)
        }
      })

      return formData
    },
    openStylePicker (styleKey) {
      this.pendingStyleKey = styleKey
      const input = this.$refs.styleFileInput
      if (!input) return
      input.value = ''
      input.click()
    },
    onStyleFileSelected (event) {
      const file = event?.target?.files?.[0]
      if (!file || !this.pendingStyleKey) return

      this.cropTargetStyleKey = this.pendingStyleKey
      this.pendingStyleKey = null
      this.clearCropSourceUrl()
      this.cropSourceUrl = URL.createObjectURL(file)
      this.cropImageNaturalWidth = 0
      this.cropImageNaturalHeight = 0
      this.cropImageRect = { x: 0, y: 0, width: 0, height: 0 }
      this.cropBox = { x: 0, y: 0, width: 0, height: 0 }
      this.cropDialog = true
    },
    onCropImageLoad () {
      const image = this.$refs.cropImage
      if (!image) return
      this.cropImageNaturalWidth = image.naturalWidth
      this.cropImageNaturalHeight = image.naturalHeight
      this.$nextTick(() => this.calculateCropGeometry(true))
    },
    handleCropResize () {
      if (!this.cropDialog) return
      this.$nextTick(() => this.calculateCropGeometry(true))
    },
    calculateCropGeometry (resetPosition = false) {
      if (!this.cropImageNaturalWidth || !this.cropImageNaturalHeight) return

      const container = this.$refs.cropContainer
      if (!container) return
      const containerWidth = container.clientWidth
      const containerHeight = container.clientHeight
      if (!containerWidth || !containerHeight) return

      const naturalWidth = this.cropImageNaturalWidth
      const naturalHeight = this.cropImageNaturalHeight
      const scale = Math.min(containerWidth / naturalWidth, containerHeight / naturalHeight)
      const drawWidth = naturalWidth * scale
      const drawHeight = naturalHeight * scale
      const imageX = (containerWidth - drawWidth) / 2
      const imageY = (containerHeight - drawHeight) / 2

      const maxHeight = Math.min(drawHeight, drawWidth / this.cropAspectRatio)
      const minHeight = Math.min(80, maxHeight)
      let boxHeight = Math.min(this.cropDisplayHeight, maxHeight)
      if (resetPosition || !this.cropBox.height) {
        boxHeight = this.clamp(maxHeight * 0.6, minHeight, maxHeight)
      } else {
        boxHeight = this.clamp(this.cropBox.height, minHeight, maxHeight)
      }
      const boxWidth = boxHeight * this.cropAspectRatio

      this.cropImageRect = { x: imageX, y: imageY, width: drawWidth, height: drawHeight }
      if (resetPosition || !this.cropBox.height) {
        this.cropBox = {
          x: imageX + (drawWidth - boxWidth) / 2,
          y: imageY + (drawHeight - boxHeight) / 2,
          width: boxWidth,
          height: boxHeight
        }
      } else {
        this.cropBox = {
          x: this.clamp(this.cropBox.x, imageX, imageX + drawWidth - boxWidth),
          y: this.clamp(this.cropBox.y, imageY, imageY + drawHeight - boxHeight),
          width: boxWidth,
          height: boxHeight
        }
      }
      this.cropSelectionNaturalWidth = this.cropBox.width / scale
      this.cropSelectionNaturalHeight = this.cropBox.height / scale
    },
    startCropResizing (event) {
      if (!this.cropReady) return
      this.cropResizing = true
      this.cropDragging = false
      this.cropResizeStart = {
        x: event.clientX,
        y: event.clientY,
        height: this.cropBox.height
      }
    },
    onCropContainerPointerDown (event) {
      if (!this.cropReady) return
      if (event.target === this.$refs.cropContainer || event.target === this.$refs.cropImage) {
        const container = this.$refs.cropContainer
        if (!container) return
        const rect = container.getBoundingClientRect()
        const clickX = event.clientX - rect.left
        const clickY = event.clientY - rect.top
        const minX = this.cropImageRect.x
        const maxX = this.cropImageRect.x + this.cropImageRect.width - this.cropBox.width
        const minY = this.cropImageRect.y
        const maxY = this.cropImageRect.y + this.cropImageRect.height - this.cropBox.height
        this.cropBox.x = this.clamp(clickX - this.cropBox.width / 2, minX, maxX)
        this.cropBox.y = this.clamp(clickY - this.cropBox.height / 2, minY, maxY)
      }
    },
    startCropDragging (event) {
      if (!this.cropReady) return
      const container = this.$refs.cropContainer
      if (!container) return
      const rect = container.getBoundingClientRect()
      this.cropDragging = true
      this.cropResizing = false
      this.cropDragOffset = {
        x: event.clientX - rect.left - this.cropBox.x,
        y: event.clientY - rect.top - this.cropBox.y
      }
    },
    onCropPointerMove (event) {
      if (!this.cropReady) return
      if (this.cropResizing) {
        const deltaX = event.clientX - this.cropResizeStart.x
        const deltaY = event.clientY - this.cropResizeStart.y
        const deltaHeightFromX = deltaX / this.cropAspectRatio
        const deltaHeight = Math.max(deltaHeightFromX, deltaY)
        const maxHeight = Math.min(
          this.cropImageRect.y + this.cropImageRect.height - this.cropBox.y,
          (this.cropImageRect.x + this.cropImageRect.width - this.cropBox.x) / this.cropAspectRatio
        )
        const nextHeight = this.clamp(this.cropResizeStart.height + deltaHeight, this.cropMinHeight, maxHeight)
        this.cropBox.height = nextHeight
        this.cropBox.width = nextHeight * this.cropAspectRatio
        const scale = this.cropImageRect.width / this.cropImageNaturalWidth
        this.cropSelectionNaturalWidth = this.cropBox.width / scale
        this.cropSelectionNaturalHeight = this.cropBox.height / scale
        return
      }
      if (!this.cropDragging) return
      const container = this.$refs.cropContainer
      if (!container) return
      const rect = container.getBoundingClientRect()
      const nextX = event.clientX - rect.left - this.cropDragOffset.x
      const nextY = event.clientY - rect.top - this.cropDragOffset.y
      const minX = this.cropImageRect.x
      const maxX = this.cropImageRect.x + this.cropImageRect.width - this.cropBox.width
      const minY = this.cropImageRect.y
      const maxY = this.cropImageRect.y + this.cropImageRect.height - this.cropBox.height
      this.cropBox.x = this.clamp(nextX, minX, maxX)
      this.cropBox.y = this.clamp(nextY, minY, maxY)
    },
    stopCropDragging () {
      this.cropDragging = false
      this.cropResizing = false
    },
    setCropSizeFromInput (value) {
      if (!this.cropReady) return
      const scale = this.cropImageRect.width / this.cropImageNaturalWidth
      if (!scale) return

      const parsed = Math.round(Number(value))
      if (!Number.isFinite(parsed) || parsed <= 0) return

      const minNaturalHeight = Math.max(1, Math.round(this.cropMinHeight / scale))
      const maxNaturalHeight = Math.max(minNaturalHeight, this.cropNaturalMaxHeight)
      const targetNaturalHeight = this.clamp(parsed, minNaturalHeight, maxNaturalHeight)
      const targetNaturalWidth = targetNaturalHeight * this.cropAspectRatio
      const nextDisplayHeight = targetNaturalHeight * scale
      const nextDisplayWidth = nextDisplayHeight * this.cropAspectRatio
      const minX = this.cropImageRect.x
      const maxX = this.cropImageRect.x + this.cropImageRect.width - nextDisplayWidth
      const minY = this.cropImageRect.y
      const maxY = this.cropImageRect.y + this.cropImageRect.height - nextDisplayHeight

      this.cropBox = {
        x: this.clamp(this.cropBox.x, minX, maxX),
        y: this.clamp(this.cropBox.y, minY, maxY),
        width: nextDisplayWidth,
        height: nextDisplayHeight
      }
      this.cropSelectionNaturalWidth = targetNaturalWidth
      this.cropSelectionNaturalHeight = targetNaturalHeight
    },
    clamp (value, min, max) {
      return Math.min(Math.max(value, min), max)
    },
    cancelCropDialog () {
      this.stopCropDragging()
      this.cropDialog = false
      this.cropTargetStyleKey = null
      this.clearCropSourceUrl()
    },
    applyCrop () {
      if (!this.cropReady || !this.cropTargetStyleKey) return
      const image = this.$refs.cropImage
      if (!image) return

      const scale = this.cropImageRect.width / this.cropImageNaturalWidth
      const sourceX = Math.round((this.cropBox.x - this.cropImageRect.x) / scale)
      const sourceY = Math.round((this.cropBox.y - this.cropImageRect.y) / scale)
      const sourceWidth = Math.round(this.cropSelectionNaturalWidth)
      const sourceHeight = Math.round(this.cropSelectionNaturalHeight)
      const outputWidth = Math.round(this.cropOutputHeight * this.cropAspectRatio)
      const outputHeight = this.cropOutputHeight

      const canvas = document.createElement('canvas')
      canvas.width = outputWidth
      canvas.height = outputHeight
      const context = canvas.getContext('2d')
      if (!context) return

      context.drawImage(
        image,
        sourceX,
        sourceY,
        sourceWidth,
        sourceHeight,
        0,
        0,
        outputWidth,
        outputHeight
      )

      const imageData = context.getImageData(0, 0, outputWidth, outputHeight)
      const pixels = imageData.data
      for (let i = 0; i < pixels.length; i += 4) {
        const alpha = pixels[i + 3]
        if (!alpha) continue

        const r = pixels[i]
        const g = pixels[i + 1]
        const b = pixels[i + 2]
        const max = Math.max(r, g, b)
        const min = Math.min(r, g, b)
        const saturation = max === 0 ? 0 : (max - min) / max
        const whiteness = (r + g + b) / 3

        if (whiteness >= 245) {
          pixels[i + 3] = 0
          continue
        }

        if (whiteness >= 225 && saturation <= 0.12) {
          const fade = (whiteness - 225) / 20
          pixels[i + 3] = Math.round(alpha * (1 - fade))
        }
      }
      context.putImageData(imageData, 0, 0)

      canvas.toBlob(blob => {
        if (!blob) {
          this.$alert.error('No se pudo recortar la imagen')
          return
        }
        const fileName = `${this.cropTargetStyleKey}-${Date.now()}.png`
        const croppedFile = new File([blob], fileName, { type: 'image/png' })
        this.setStyleFile(this.cropTargetStyleKey, croppedFile)
        this.cropDialog = false
        this.cropTargetStyleKey = null
        this.clearCropSourceUrl()
      }, 'image/png')
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
      this.$alert.dialog('Desea eliminar el personaje?')
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
.style-thumb-lg {
  height: 140px;
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
  position: relative;
  width: 100%;
}
.style-menu-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 5;
}
.hidden-file-input {
  display: none;
}
.preview-dialog-image {
  border: 2px solid #212121;
  background: #fafafa;
}
.all-styles-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(140px, 1fr));
  gap: 12px;
}
.all-style-card {
  display: flex;
  flex-direction: column;
}
.all-style-box {
  position: relative;
}
.all-style-image {
  height: 280px;
  border-radius: 8px;
  border: 2px solid #212121;
  background: #fafafa;
}
.all-style-menu-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 5;
}
.all-style-empty {
  background: #f1f1f1;
}
.crop-container {
  position: relative;
  width: 100%;
  height: 60vh;
  min-height: 360px;
  background: #f5f5f5;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 8px;
  overflow: hidden;
  touch-action: none;
}
.crop-image {
  position: absolute;
  user-select: none;
  -webkit-user-drag: none;
  z-index: 1;
}
.crop-box {
  position: absolute;
  border: 2px solid #1976d2;
  background: rgba(25, 118, 210, 0.15);
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.28);
  cursor: move;
  z-index: 2;
}
.crop-grid {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background-image:
    linear-gradient(to right, rgba(255, 255, 255, 0.55) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(255, 255, 255, 0.55) 1px, transparent 1px);
  background-size: 33.333% 100%, 100% 25%;
}
.crop-box-handle {
  position: absolute;
  width: 16px;
  height: 16px;
  right: -8px;
  bottom: -8px;
  border-radius: 50%;
  background: #1976d2;
  border: 2px solid #fff;
  cursor: nwse-resize;
}
@media (max-width: 900px) {
  .styles-grid {
    grid-template-columns: repeat(2, minmax(90px, 1fr));
    min-width: 220px;
  }
  .all-styles-grid {
    grid-template-columns: repeat(2, minmax(120px, 1fr));
  }
  .all-style-image {
    height: 220px;
  }
  .crop-container {
    height: 50vh;
    min-height: 300px;
  }
}
</style>

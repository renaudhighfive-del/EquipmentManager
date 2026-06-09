<script setup>
import { ref, onMounted, reactive } from 'vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import SideModal from '../../components/layout/SideModal.vue'
import { AlertTriangle, Plus, X, Upload, Loader2, Image as ImageIcon } from 'lucide-vue-next'
import { useEquipementStore } from '../../stores/equipement'
import { usePanneStore } from '../../stores/panne'
import { useToast } from 'primevue/usetoast'

const equipementStore = useEquipementStore()
const panneStore = usePanneStore()
const toast = useToast()

const showModal = ref(false)
const isSubmitting = ref(false)

const form = reactive({
  equipement_id: '',
  gravite: 'moyenne',
  description: ''
})

const photos = ref([])
const photoPreviews = ref([])

onMounted(() => {
  equipementStore.fetchEquipements()
  panneStore.fetchPannes()
})

const onFileChange = (e) => {
  const files = Array.from(e.target.files)
  files.forEach(file => {
    photos.value.push(file)
    const reader = new FileReader()
    reader.onload = (e) => {
      photoPreviews.value.push(e.target.result)
    }
    reader.readAsDataURL(file)
  })
}

const removePhoto = (index) => {
  photos.value.splice(index, 1)
  photoPreviews.value.splice(index, 1)
}

const resetForm = () => {
  form.equipement_id = ''
  form.gravite = 'moyenne'
  form.description = ''
  photos.value = []
  photoPreviews.value = []
}

const handleSubmit = async () => {
  if (!form.equipement_id || !form.description) {
    toast.add({ severity: 'warn', summary: 'Attention', detail: 'Veuillez remplir tous les champs obligatoires', life: 3000 })
    return
  }

  isSubmitting.value = true
  try {
    const formData = new FormData()
    formData.append('equipement_id', form.equipement_id)
    formData.append('gravite', form.gravite)
    formData.append('description', form.description)
    
    photos.value.forEach((file) => {
      formData.append('images[]', file)
    })

    const success = await panneStore.createPanne(formData)
    if (success) {
      toast.add({ severity: 'success', summary: 'Succès', detail: panneStore.successMessage, life: 3000 })
      showModal.value = false
      resetForm()
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: panneStore.error || 'Échec de la déclaration', life: 3000 })
  } finally {
    isSubmitting.value = false
  }
}

const getStatutLabel = (statut) => {
  const labels = {
    declaree:       'Déclarée',
    en_cours:       'En cours',
    en_maintenance: 'En maintenance',
    resolue:        'Résolue',
    irrecuperable:  'Irrécupérable',
  }
  return labels[statut] || statut
}

const getStatutClass = (statut) => {
  const map = {
    declaree:       'bg-amber-50 text-amber-600',
    en_cours:       'bg-blue-50 text-blue-600',
    en_maintenance: 'bg-purple-50 text-purple-600',
    resolue:        'bg-emerald-50 text-emerald-600',
    irrecuperable:  'bg-rose-50 text-rose-600',
  }
  return map[statut] ?? 'bg-slate-50 text-slate-600'
}

const getGraviteClass = (gravite) => {
  const map = {
    faible:   'bg-blue-50 text-blue-600',
    moyenne:  'bg-amber-50 text-amber-600',
    critique: 'bg-red-50 text-red-600',
  }
  return map[gravite] ?? 'bg-slate-50 text-slate-600'
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader title="Mes pannes" subtitle="Suivez l'état de vos signalements de panne">
      <template #actions>
        <button 
          @click="showModal = true"
          class="flex items-center gap-2 px-5 py-2.5 bg-rose-600 rounded-xl text-sm font-bold text-white hover:bg-rose-700 transition-all shadow-lg shadow-rose-200"
        >
          <Plus class="w-4 h-4" />
          Nouvelle déclaration
        </button>
      </template>
    </PageHeader>

    <!-- Liste des pannes -->
    <div v-if="panneStore.loading && panneStore.pannes.length === 0" class="space-y-4">
      <div v-for="i in 3" :key="i" class="h-24 bg-slate-100 animate-pulse rounded-2xl"></div>
    </div>

    <div v-else-if="panneStore.pannes.length === 0" class="bg-white p-16 rounded-3xl border border-slate-200 shadow-sm text-center">
      <div class="w-16 h-16 bg-rose-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <AlertTriangle class="w-8 h-8 text-rose-400" />
      </div>
      <h3 class="text-lg font-bold text-slate-900 mb-2">Aucune panne déclarée</h3>
      <p class="text-slate-500 text-sm mb-6">Tout fonctionne correctement. En cas de problème, signalez-le ici.</p>
      <button 
        @click="showModal = true"
        class="px-6 py-3 bg-rose-600 text-white font-bold rounded-xl hover:bg-rose-700 transition-all text-sm"
      >
        Déclarer une panne
      </button>
    </div>

    <div v-else class="grid grid-cols-1 gap-4">
      <div v-for="panne in panneStore.pannes" :key="panne.id" class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:shadow-md transition-all border-l-4" :class="panne.statut === 'declaree' ? 'border-l-amber-400' : 'border-l-emerald-500'">
        <div class="flex items-center gap-4 sm:gap-5">
          <div :class="['w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center flex-shrink-0', getGraviteClass(panne.gravite)]">
            <AlertTriangle class="w-6 h-6 sm:w-7 sm:h-7" />
          </div>
          <div class="min-w-0">
            <p class="font-bold text-slate-900 truncate">{{ panne.equipement?.marque }} {{ panne.equipement?.modele }}</p>
            <p class="text-xs text-slate-500 font-medium mt-0.5 line-clamp-1">{{ panne.description }}</p>
            <div class="flex items-center gap-3 mt-1">
              <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">{{ formatDate(panne.date_declaration) }}</p>
              <span v-if="panne.valide_par" class="text-[9px] font-black text-emerald-600 uppercase bg-emerald-50 px-1.5 py-0.5 rounded">
                Validé par {{ panne.valide_par?.name || 'Admin' }}
              </span>
            </div>
          </div>
        </div>
        <div class="flex items-center justify-between sm:justify-end gap-3 border-t sm:border-t-0 pt-3 sm:pt-0">
          <span :class="['px-3 py-1 rounded-lg text-[10px] font-black uppercase', getGraviteClass(panne.gravite)]">
            {{ panne.gravite }}
          </span>
          <span :class="['px-3 py-1 rounded-lg text-[10px] font-black uppercase', getStatutClass(panne.statut)]">
            {{ getStatutLabel(panne.statut) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Modal déclaration -->
    <SideModal :show="showModal" title="Déclarer une panne" @close="showModal = false; resetForm()">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Photo Upload Section -->
        <div class="space-y-4">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Photos du problème (facultatif)</label>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div v-for="(preview, index) in photoPreviews" :key="index" class="relative aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 group">
              <img :src="preview" class="w-full h-full object-cover">
              <button type="button" @click="removePhoto(index)" class="absolute top-1 right-1 p-1 bg-black/50 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                <X class="w-3 h-3" />
              </button>
            </div>
            <label class="relative aspect-square rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-rose-400 hover:bg-rose-50 transition-all text-slate-400 hover:text-rose-600">
              <input type="file" multiple accept="image/*" class="hidden" @change="onFileChange">
              <Upload class="w-6 h-6" />
              <span class="text-[10px] font-bold">Ajouter</span>
            </label>
          </div>
        </div>

        <div class="space-y-5">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Équipement concerné</label>
            <select v-model="form.equipement_id" required class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-rose-500/20">
              <option value="">Sélectionner un équipement</option>
              <option v-for="equip in equipementStore.equipements" :key="equip.id" :value="equip.id">
                {{ equip.marque }} {{ equip.modele }} — {{ equip.reference }}
              </option>
            </select>
          </div>
          
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Gravité</label>
            <select v-model="form.gravite" class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-rose-500/20">
              <option value="faible">Faible (N'empêche pas l'utilisation)</option>
              <option value="moyenne">Moyenne (Utilisation dégradée)</option>
              <option value="critique">Critique (Inutilisable)</option>
            </select>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Description du problème</label>
            <textarea 
              v-model="form.description"
              required
              rows="4"
              placeholder="Décrivez précisément le problème rencontré..."
              class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-rose-500/20 resize-none"
            ></textarea>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showModal = false; resetForm()" class="flex-1 h-12 border border-slate-200 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all">
              Annuler
            </button>
            <button 
              type="submit" 
              :disabled="isSubmitting"
              class="flex-1 h-12 bg-rose-600 text-white rounded-xl text-sm font-black shadow-lg shadow-rose-200 hover:bg-rose-700 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
              {{ isSubmitting ? 'Envoi...' : 'Soumettre' }}
            </button>
          </div>
        </div>
      </form>
    </SideModal>
  </div>
</template>

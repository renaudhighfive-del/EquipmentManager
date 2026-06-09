<script setup>
import { ref, onMounted, reactive, computed } from 'vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import SideModal from '../../components/layout/SideModal.vue'
import { 
  AlertTriangle, 
  Plus, 
  X, 
  Upload, 
  Loader2, 
  Image as ImageIcon, 
  AlertOctagon, 
  Calendar, 
  Smartphone,
  CheckCircle2,
  XCircle,
  Clock,
  Search,
  FileText
} from 'lucide-vue-next'
import { useEquipementStore } from '../../stores/equipement'
import { usePanneStore } from '../../stores/panne'
import { useSinistreStore } from '../../stores/sinistre'
import { useToast } from 'primevue/usetoast'

const equipementStore = useEquipementStore()
const panneStore = usePanneStore()
const sinistreStore = useSinistreStore()
const toast = useToast()

const showModal = ref(false)
const isSubmitting = ref(false)
const searchQuery = ref('')
const incidentType = ref('panne') // 'panne', 'perte', 'vol', 'casse'

const form = reactive({
  equipement_id: '',
  gravite: 'moyenne',
  description: '',
})

const photos = ref([])
const photoPreviews = ref([])

onMounted(async () => {
  await Promise.all([
    equipementStore.fetchEquipements(),
    panneStore.fetchPannes(),
    sinistreStore.fetchSinistres()
  ])
})

const allIncidents = computed(() => {
  const pannes = panneStore.pannes.map(p => ({
    ...p,
    incidentType: 'panne',
    date: p.date_declaration || p.created_at,
    displayType: 'Panne',
    displayStatus: p.statut,
  }))

  const sinistres = sinistreStore.sinistres.map(s => ({
    ...s,
    incidentType: 'sinistre',
    date: s.created_at || s.date,
    displayType: s.type ? s.type.charAt(0).toUpperCase() + s.type.slice(1) : 'Sinistre',
    displayStatus: s.status,
  }))

  let combined = [...pannes, ...sinistres]

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    combined = combined.filter(i => 
      i.equipement?.reference?.toLowerCase().includes(q) ||
      i.equipement?.marque?.toLowerCase().includes(q) ||
      i.equipement?.modele?.toLowerCase().includes(q) ||
      i.description?.toLowerCase().includes(q)
    )
  }

  return combined.sort((a, b) => new Date(b.date) - new Date(a.date))
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
  incidentType.value = 'panne'
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
    formData.append('description', form.description)
    
    photos.value.forEach((file) => {
      formData.append('images[]', file)
    })

    if (incidentType.value === 'panne') {
      formData.append('gravite', form.gravite)
      await panneStore.createPanne(formData)
    } else {
      // Sinistre (Perte, Vol, Casse)
      formData.append('type', incidentType.value)
      await sinistreStore.declareSinistre(formData)
    }

    toast.add({ severity: 'success', summary: 'Succès', detail: 'Déclaration envoyée avec succès', life: 3000 })
    showModal.value = false
    resetForm()
    // Refresh lists
    await Promise.all([panneStore.fetchPannes(), sinistreStore.fetchSinistres()])
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de la déclaration', life: 3000 })
  } finally {
    isSubmitting.value = false
  }
}

const getStatusBadgeClass = (incident) => {
  const status = incident.incidentType === 'panne' ? incident.statut : incident.status
  
  switch (status) {
    case 'declaree':
    case 'en_attente_validation':
      return 'bg-amber-50 text-amber-600 border-amber-100'
    case 'en_cours':
    case 'en_maintenance':
    case 'validee':
      return 'bg-emerald-50 text-emerald-600 border-emerald-100'
    case 'resolue':
    case 'cloturee':
      return 'bg-slate-50 text-slate-500 border-slate-100'
    case 'rejetee':
    case 'irrecuperable':
      return 'bg-rose-50 text-rose-600 border-rose-100'
    default:
      return 'bg-slate-50 text-slate-500 border-slate-100'
  }
}

const getStatusLabel = (incident) => {
  const status = incident.incidentType === 'panne' ? incident.statut : incident.status
  
  const labels = {
    declaree: 'Déclarée',
    en_attente_validation: 'En attente',
    en_cours: 'Validée',
    en_maintenance: 'En maintenance',
    validee: 'Validée',
    rejetee: 'Rejetée',
    resolue: 'Résolue',
    cloturee: 'Clôturée',
    irrecuperable: 'Rejetée'
  }
  return labels[status] || status
}

const getIncidentTypeClass = (type) => {
  switch (type.toLowerCase()) {
    case 'panne': return 'bg-blue-50 text-blue-600 border-blue-100'
    case 'perte': return 'bg-rose-50 text-rose-600 border-rose-100'
    case 'vol': return 'bg-slate-900 text-white border-slate-900'
    case 'casse': return 'bg-orange-50 text-orange-600 border-orange-100'
    default: return 'bg-slate-50 text-slate-600 border-slate-100'
  }
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
    <PageHeader title="Mes Incidents" subtitle="Suivez l'état de vos signalements de panne et sinistres">
      <template #actions>
        <button 
          @click="showModal = true"
          class="flex items-center gap-2 px-5 py-2.5 bg-rose-600 rounded-xl text-sm font-bold text-white hover:bg-rose-700 transition-all shadow-lg shadow-rose-200"
        >
          <Plus class="w-4 h-4" />
          Déclarer un incident
        </button>
      </template>
    </PageHeader>

    <!-- Search Bar -->
    <div class="relative max-w-md">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
      <input 
        v-model="searchQuery"
        type="text" 
        placeholder="Rechercher par référence, marque, modèle..." 
        class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500/10 outline-none transition-all shadow-sm"
      >
    </div>

    <!-- Loading State -->
    <div v-if="(panneStore.loading || sinistreStore.loading) && allIncidents.length === 0" class="space-y-4">
      <div v-for="i in 3" :key="i" class="h-24 bg-slate-100 animate-pulse rounded-2xl"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="allIncidents.length === 0" class="bg-white p-16 rounded-3xl border border-slate-200 shadow-sm text-center">
      <div class="w-16 h-16 bg-rose-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <AlertTriangle class="w-8 h-8 text-rose-400" />
      </div>
      <h3 class="text-lg font-bold text-slate-900 mb-2">Aucun incident déclaré</h3>
      <p class="text-slate-500 text-sm mb-6">Tout fonctionne correctement. En cas de problème, signalez-le ici.</p>
      <button 
        @click="showModal = true"
        class="px-6 py-3 bg-rose-600 text-white font-bold rounded-xl hover:bg-rose-700 transition-all text-sm"
      >
        Déclarer un incident
      </button>
    </div>

    <!-- Incidents List -->
    <div v-else class="grid grid-cols-1 gap-4">
      <div 
        v-for="incident in allIncidents" 
        :key="incident.incidentType + '-' + incident.id" 
        class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:shadow-md transition-all border-l-4" 
        :class="[
          incident.displayStatus === 'declaree' || incident.displayStatus === 'en_attente_validation' ? 'border-l-amber-400' : 'border-l-emerald-500'
        ]"
      >
        <div class="flex items-center gap-4 sm:gap-5">
          <div :class="['w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center flex-shrink-0 border', getIncidentTypeClass(incident.displayType)]">
            <AlertTriangle v-if="incident.incidentType === 'panne'" class="w-6 h-6 sm:w-7 sm:h-7" />
            <AlertOctagon v-else class="w-6 h-6 sm:w-7 sm:h-7" />
          </div>
          <div class="min-w-0">
            <div class="flex items-center gap-2 mb-0.5">
              <span :class="['px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider border', getIncidentTypeClass(incident.displayType)]">
                {{ incident.displayType }}
              </span>
              <p class="font-bold text-slate-900 truncate">{{ incident.equipement?.marque }} {{ incident.equipement?.modele }}</p>
            </div>
            <p class="text-xs text-slate-500 font-medium mt-0.5 line-clamp-1">{{ incident.description }}</p>
            <div class="flex items-center gap-3 mt-1">
              <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">{{ formatDate(incident.date) }}</p>
              <span v-if="incident.valide_par || incident.validated_by" class="text-[9px] font-black text-emerald-600 uppercase bg-emerald-50 px-1.5 py-0.5 rounded">
                Validé
              </span>
            </div>
          </div>
        </div>
        <div class="flex items-center justify-between sm:justify-end gap-3 border-t sm:border-t-0 pt-3 sm:pt-0">
          <span v-if="incident.incidentType === 'panne'" :class="['px-3 py-1 rounded-lg text-[10px] font-black uppercase', incident.gravite === 'critique' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600']">
            {{ incident.gravite }}
          </span>
          <span :class="['px-3 py-1 rounded-lg text-[10px] font-black uppercase border', getStatusBadgeClass(incident)]">
            {{ getStatusLabel(incident) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Unified Declaration Modal -->
    <SideModal :show="showModal" title="Déclarer un incident" @close="showModal = false; resetForm()">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        
        <!-- Selection du type d'incident -->
        <div class="space-y-3">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Que souhaitez-vous signaler ?</label>
          <div class="grid grid-cols-2 gap-3">
            <button 
              type="button"
              @click="incidentType = 'panne'"
              :class="['p-4 rounded-2xl border-2 transition-all text-left flex flex-col gap-2', incidentType === 'panne' ? 'border-primary-600 bg-primary-50/50' : 'border-slate-100 hover:border-slate-200']"
            >
              <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                <AlertTriangle class="w-6 h-6" />
              </div>
              <div>
                <p class="text-sm font-black text-slate-900">Une panne</p>
                <p class="text-[10px] text-slate-500 font-medium">Problème technique ou matériel</p>
              </div>
            </button>
            
            <button 
              type="button"
              @click="incidentType = 'perte'"
              :class="['p-4 rounded-2xl border-2 transition-all text-left flex flex-col gap-2', ['perte', 'vol', 'casse'].includes(incidentType) ? 'border-rose-600 bg-rose-50/50' : 'border-slate-100 hover:border-slate-200']"
            >
              <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
                <AlertOctagon class="w-6 h-6" />
              </div>
              <div>
                <p class="text-sm font-black text-slate-900">Un sinistre</p>
                <p class="text-[10px] text-slate-500 font-medium">Perte, vol ou casse</p>
              </div>
            </button>
          </div>
        </div>

        <!-- Sous-type pour Sinistre -->
        <div v-if="['perte', 'vol', 'casse'].includes(incidentType)" class="space-y-3 animate-in slide-in-from-top-2 duration-300">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Type de sinistre</label>
          <div class="flex gap-2">
            <button 
              v-for="type in ['perte', 'vol', 'casse']" 
              :key="type"
              type="button"
              @click="incidentType = type"
              :class="['flex-1 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider border transition-all', incidentType === type ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-500 border-slate-200']"
            >
              {{ type }}
            </button>
          </div>
        </div>

        <!-- Photo Upload (Pannes & Sinistres) -->
        <div class="space-y-4 animate-in slide-in-from-top-2 duration-300">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Photos justificatives (facultatif)</label>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div v-for="(preview, index) in photoPreviews" :key="index" class="relative aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 group">
              <img :src="preview" class="w-full h-full object-cover">
              <button type="button" @click="removePhoto(index)" class="absolute top-1 right-1 p-1 bg-black/50 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                <X class="w-3 h-3" />
              </button>
            </div>
            <label class="relative aspect-square rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-primary-400 hover:bg-primary-50 transition-all text-slate-400 hover:text-primary-600">
              <input type="file" multiple accept="image/*" class="hidden" @change="onFileChange">
              <Upload class="w-6 h-6" />
              <span class="text-[10px] font-bold">Ajouter</span>
            </label>
          </div>
        </div>

        <!-- Champs communs -->
        <div class="space-y-5">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Équipement concerné</label>
            <select v-model="form.equipement_id" required class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-primary-500/20">
              <option value="">Sélectionner un équipement</option>
              <option v-for="equip in equipementStore.equipements" :key="equip.id" :value="equip.id">
                {{ equip.reference }} : {{ equip.marque }} {{ equip.modele }}
              </option>
            </select>
          </div>

          <div v-if="incidentType === 'panne'" class="space-y-1.5 animate-in slide-in-from-top-2 duration-300">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Gravité de la panne</label>
            <select v-model="form.gravite" class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-primary-500/20">
              <option value="faible">Faible (fonctionne encore)</option>
              <option value="moyenne">Moyenne (gênant)</option>
              <option value="critique">Critique (inutilisable)</option>
            </select>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Description & Circonstances</label>
            <textarea 
              v-model="form.description" 
              required
              rows="4" 
              class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-primary-500/20 resize-none"
              placeholder="Expliquez brièvement le problème..."
            ></textarea>
          </div>
        </div>

        <div class="pt-4">
          <button 
            type="submit" 
            :disabled="isSubmitting"
            class="w-full h-12 bg-primary-600 text-white font-black rounded-xl hover:bg-primary-700 transition-all shadow-lg shadow-primary-200 flex items-center justify-center gap-2 disabled:opacity-50"
          >
            <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin" />
            {{ isSubmitting ? 'Envoi en cours...' : 'Confirmer la déclaration' }}
          </button>
        </div>
      </form>
    </SideModal>
  </div>
</template>

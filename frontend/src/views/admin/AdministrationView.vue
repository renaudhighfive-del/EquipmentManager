<script setup>
import { ref, onMounted, computed } from 'vue';
import PageHeader from '../../components/layout/PageHeader.vue';
import SideModal from '../../components/layout/SideModal.vue';
import api from '../../services/axios';
import { 
  UserPlus, 
  Search, 
  Shield, 
  Mail, 
  Power, 
  PowerOff, 
  Edit3,
  MoreVertical,
  Loader2
} from 'lucide-vue-next';

const showUserModal = ref(false);
const selectedUser = ref(null);
const loading = ref(false);
const users = ref([]);
const searchQuery = ref('');

const form = ref({
  name: '',
  email: '',
  role: 'agent',
  password: ''
});

// const fetchUsers = async () => {
//   loading.value = true;
//   try {
//     const response = await api.get('/users');
//     users.value = response.data;
//   } catch (error) {
//     console.error('Erreur lors de la récupération des utilisateurs:', error);
//   } finally {
//     loading.value = false;
//   }
// };

// const filteredUsers = computed(() => {
//   if (!searchQuery.value) return users.value;
//   const query = searchQuery.value.toLowerCase();
//   return users.value.filter(user => 
//     user.name.toLowerCase().includes(query) || 
//     user.email.toLowerCase().includes(query)
//   );
// });

// const toggleUserStatus = async (user) => {
//   try {
//     const response = await api.patch(`/users/${user.id}/toggle-status`);
//     user.is_active = !user.is_active;
//     // Notification de succès si nécessaire
//   } catch (error) {
//     console.error('Erreur lors du changement de statut:', error);
//   }
// };

// const openAddUser = () => {
//   selectedUser.value = null;
//   form.value = {
//     name: '',
//     email: '',
//     role: 'agent',
//     password: ''
//   };
//   showUserModal.value = true;
// };

// const openEditUser = (user) => {
//   selectedUser.value = user;
//   form.value = {
//     name: user.name,
//     email: user.email,
//     role: user.role,
//     password: '' // On ne récupère pas le mot de passe
//   };
//   showUserModal.value = true;
// };

// const submitForm = async () => {
//   try {
//     if (selectedUser.value) {
//       await api.put(`/users/${selectedUser.value.id}`, form.value);
//     } else {
//       await api.post('/users', form.value);
//     }
//     showUserModal.value = false;
//     fetchUsers();
//   } catch (error) {
//     console.error('Erreur lors de l\'enregistrement:', error);
//   }
// };

// const getRoleBadge = (role) => {
//   switch (role) {
//     case 'admin': return 'bg-rose-50 text-rose-600 border-rose-100';
//     case 'gestionnaire': return 'bg-amber-50 text-amber-600 border-amber-100';
//     default: return 'bg-blue-50 text-blue-600 border-blue-100';
//   }
// };

onMounted(fetchUsers);
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader 
      title="Administration" 
      subtitle="Gestion des accès et comptes utilisateurs"
    >
      <template #actions>
        <button @click="openAddUser" class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">
          <UserPlus class="w-4 h-4" />
          Nouvel utilisateur
        </button>
      </template>
    </PageHeader>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-6 border-b border-slate-50 flex items-center gap-4">
        <div class="relative flex-1 max-w-md">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Rechercher un utilisateur..." 
            class="w-full h-10 pl-10 pr-4 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary-500/10 outline-none"
          >
        </div>
        <div v-if="loading" class="flex items-center gap-2 text-slate-400">
          <Loader2 class="w-4 h-4 animate-spin" />
          <span class="text-xs font-medium">Chargement...</span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-slate-50/50 text-[11px] font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">
              <th class="px-8 py-5">Utilisateur</th>
              <th class="px-6 py-5">Rôle</th>
              <th class="px-6 py-5">Statut</th>
              <th class="px-8 py-5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="user in filteredUsers" :key="user.id" class="group hover:bg-slate-50/50 transition-colors">
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white font-bold shadow-lg shadow-primary-100">
                    {{ user.name.charAt(0) }}
                  </div>
                  <div>
                    <p class="text-sm font-bold text-slate-900">{{ user.name }}</p>
                    <p class="text-xs text-slate-500 font-medium">{{ user.email }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-5">
                <span :class="['px-3 py-1 rounded-lg text-[10px] font-bold border uppercase tracking-wider', getRoleBadge(user.role)]">
                  {{ user.role }}
                </span>
              </td>
              <td class="px-6 py-5">
                <div class="flex items-center gap-2">
                  <div :class="['w-2 h-2 rounded-full shadow-sm', user.is_active ? 'bg-emerald-500 shadow-emerald-100' : 'bg-slate-300 shadow-slate-100']"></div>
                  <span :class="['text-xs font-bold', user.is_active ? 'text-emerald-600' : 'text-slate-400']">
                    {{ user.is_active ? 'Actif' : 'Inactif' }}
                  </span>
                </div>
              </td>
              <td class="px-8 py-5 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button 
                    @click="toggleUserStatus(user)"
                    :title="user.is_active ? 'Désactiver' : 'Activer'"
                    :class="['p-2 rounded-lg transition-all', user.is_active ? 'text-amber-500 hover:bg-amber-50' : 'text-emerald-500 hover:bg-emerald-50']"
                  >
                    <component :is="user.is_active ? PowerOff : Power" class="w-4.5 h-4.5" />
                  </button>
                  <button @click="openEditUser(user)" class="p-2 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all">
                    <Edit3 class="w-4.5 h-4.5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- User Edit Modal -->
    <SideModal 
      :show="showUserModal" 
      :title="selectedUser ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur'" 
      mode="center"
      @close="showUserModal = false"
    >
      <form @submit.prevent="submitForm" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Nom complet</label>
            <input 
              v-model="form.name"
              type="text" 
              required
              class="w-full h-12 px-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500/50 transition-all"
              placeholder="ex: Jean Dupont"
            >
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Email professionnel</label>
            <input 
              v-model="form.email"
              type="email" 
              required
              class="w-full h-12 px-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500/50 transition-all"
              placeholder="email@entreprise.com"
            >
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Rôle</label>
            <select 
              v-model="form.role"
              class="w-full h-12 px-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500/50 transition-all appearance-none"
            >
              <option value="admin">Administrateur</option>
              <option value="gestionnaire">Gestionnaire de parc</option>
              <option value="agent">Agent terrain</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase ml-1">
              {{ selectedUser ? 'Nouveau mot de passe (optionnel)' : 'Mot de passe' }}
            </label>
            <input 
              v-model="form.password"
              type="password" 
              :required="!selectedUser"
              class="w-full h-12 px-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500/50 transition-all"
              placeholder="••••••••"
            >
          </div>
        </div>
        
        <div class="pt-6">
          <button type="submit" class="w-full py-4 bg-primary-600 text-white font-bold rounded-2xl hover:bg-primary-700 transition-all shadow-xl shadow-primary-200 flex items-center justify-center gap-2">
            <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
            {{ selectedUser ? 'Enregistrer les modifications' : 'Créer l\'utilisateur' }}
          </button>
        </div>
      </form>
    </SideModal>
  </div>
</template>

<template>
  <app-layout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Create Trade

        <div class="flex-1 inline-block float-right">
          <multiselect
            class="rounded-md border border-black"
            placeholder="Select Project."
            v-model="proj_id"
            track-by="id"
            label="name"
            :options="options"
            @update:model-value="projch"
          >
          </multiselect>
        </div>
      </h2>
    </template>
    <div
      v-if="$page.props.flash.warning"
      class="bg-yellow-600 text-white text-center"
    >
      {{ $page.props.flash.warning }}
    </div>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-4">
      <div class="">
        <form @submit.prevent="form.post(route('trades.store'))">
          <div class="p-2 mr-2 mb-2 mt-4 ml-6 flex flex-wrap">
            <label class="my-2 mr-8 text-right w-36 font-bold">Name :</label>
            <input
              type="text"
              v-model="form.name"
              class="
                pr-2
                pb-2
                w-full
                lg:w-1/4
                rounded-md
                placeholder-indigo-300
              "
              label="name"
              placeholder="Enter Trade name:"
            />
            <div
              class="
                ml-2
                bg-red-100
                border border-red-400
                text-red-700
                px-4
                py-2
                rounded
                relative
              "
              role="alert"
              v-if="errors.name"
            >
              {{ errors.name }}
            </div>
          </div>

          <!-- <div class="p-2 mr-2 mb-2 ml-6 flex flex-wrap">
            <label class="my-2 mr-8 text-right w-36 font-bold"
              >Select Project :</label
            >

            <multiselect
              style="display: inline-block; width: 25%"
              class="rounded-md border border-black"
              placeholder="Select Project."
              v-model="form.project_id"
              track-by="id"
              label="name"
              :options="projects"
            >
            </multiselect>
            <div v-if="errors.project_id">
              {{ errors.project_id }}
            </div>
          </div> -->

          <div class="p-2 mr-2 mb-2 mt-4 ml-6 flex flex-wrap">
            <label class="my-2 mr-8 text-right w-36 font-bold"
              >Start Date :</label
            >
            <!-- :min="form.project_id['start']" -->
            <input
              type="date"
              v-model="form.start"
              :max="form.end"
              class="
                pr-2
                pb-2
                w-full
                lg:w-1/4
                rounded-md
                placeholder-indigo-300
              "
              label="start_date"
              placeholder="Enter Start Date:"
            />
            <div
              class="
                ml-2
                bg-red-100
                border border-red-400
                text-red-700
                px-4
                py-2
                rounded
                relative
              "
              role="alert"
              v-if="errors.start"
            >
              {{ errors.start }}
            </div>
          </div>

          <div class="p-2 mr-2 mb-2 mt-4 ml-6 flex flex-wrap">
            <label class="my-2 mr-8 text-right w-36 font-bold"
              >End Date :</label
            >
            <!-- :max="form.project_id['end']" -->
            <input
              type="date"
              v-model="form.end"
              :min="form.start"
              class="
                pr-2
                pb-2
                w-full
                lg:w-1/4
                rounded-md
                placeholder-indigo-300
              "
              label="start_date"
              placeholder="Enter End Date:"
            />
            <div
              class="
                ml-2
                bg-red-100
                border border-red-400
                text-red-700
                px-4
                py-2
                rounded
                relative
              "
              role="alert"
              v-if="errors.end"
            >
              {{ errors.end }}
            </div>
          </div>

          <div class="p-2 mr-2 mb-2 mt-4 ml-6 flex flex-wrap">
            <label class="my-2 mr-8 text-right w-36 font-bold">Revenue :</label>

            <input
              :disabled="form.cost > 0"
              type="number"
              v-model="form.revenue"
              class="
                pr-2
                pb-2
                w-full
                lg:w-1/4
                rounded-md
                placeholder-indigo-300
              "
              label="revenue"
              placeholder="Enter revenue:"
            />
            <div v-if="errors.revenue">{{ errors.revenue }}</div>
          </div>

          <div class="p-2 mr-2 mb-2 mt-4 ml-6 flex flex-wrap">
            <label class="my-2 mr-8 text-right w-36 font-bold">Cost :</label>

            <input
              :disabled="form.revenue > 0"
              type="number"
              v-model="form.cost"
              class="
                pr-2
                pb-2
                w-full
                lg:w-1/4
                rounded-md
                placeholder-indigo-300
              "
              label="cost"
              placeholder="Enter cost:"
            />
            <div v-if="errors.cost">{{ errors.cost }}</div>
          </div>

          <!-- <div class="p-2 mr-2 mb-2 mt-4 ml-6 flex flex-wrap">
            <label class="my-2 mr-8 text-right w-36 font-bold"
              >Select Type :</label
            >
            <select
              class="
                pr-2
                pb-2
                w-full
                lg:w-1/4
                rounded-md
                placeholder-indigo-300
              "
              label="type"
              placeholder="Enter Type:"
              v-model="form.actual"
            >
              <option :value="0">Estimate</option>
              <option :value="1">Actual</option>
            </select>

            <div v-if="errors.actual">{{ errors.actual }}</div>
          </div> -->

          <div
            class="
              px-4
              py-2
              bg-gray-100
              border-t border-gray-200
              flex
              justify-center
              items-center
            "
          >
            <button
              class="border bg-indigo-300 rounded-xl px-4 py-2 ml-4 mt-4"
              type="submit"
              :disabled="form.processing"
            >
              Create Trade
            </button>
          </div>
        </form>
      </div>
    </div>
  </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import { useForm } from "@inertiajs/inertia-vue3";
import Multiselect from "@suadelabs/vue3-multiselect";

export default {
  components: {
    AppLayout,
    Multiselect,
  },

  props: {
    errors: Object,
    projects: Array,
    projchange: Object,
  },

  data() {
    return {
      options: this.projects,
      proj_id: this.projchange,
      // co_id: this.$page.props.co_id,
      // yr_id: this.$page.props.yr_id,
    };
  },

  setup(props) {
    const form = useForm({
      name: null,
      // start: new Date().toISOString().substr(0, 10),
      // end: new Date().toISOString().substr(0, 10),
      start: null,
      end: null,
      revenue: null,
      cost: null,
      actual: 0,
      project_id: null,
    });
    return { form };
  },

  methods: {
    projch() {
      this.$inertia.get(route("projects.projch", this.proj_id));
    },
  },
};
</script>

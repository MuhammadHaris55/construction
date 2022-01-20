<template>
  <app-layout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Edit Trade
      </h2>
    </template>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-4">
      <div class="">
        <form @submit.prevent="submit">
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
                text-center
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

          <div class="p-2 mr-2 mb-2 mt-4 ml-6 flex flex-wrap">
            <label class="my-2 mr-8 text-right w-36 font-bold"
              >Start Date :</label
            >
            <input
              type="date"
              v-model="form.start"
              :min="form.project_id['start']"
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
            <input
              type="date"
              v-model="form.end"
              :max="form.project_id['end']"
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
              type="number"
              :disabled="form.cost > 0"
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
              type="number"
              :disabled="form.revenue > 0"
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
            >
              Update Trade
            </button>
          </div>
        </form>
      </div>
    </div>
  </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import Label from "../../Jetstream/Label.vue";
import Multiselect from "@suadelabs/vue3-multiselect";

export default {
  components: {
    AppLayout,
    Multiselect,
  },

  props: {
    errors: Object,
    trade: Object,
    projects: Array,
    project: Object,
  },

  data() {
    return {
      form: this.$inertia.form({
        name: this.trade.name,
        start: this.trade.start,
        end: this.trade.end,
        revenue: this.trade.revenue,
        cost: this.trade.cost,
        actual: this.trade.actual,
        project_id: this.project,
      }),
    };
  },

  methods: {
    submit() {
      this.$inertia.put(route("trades.update", this.trade.id), this.form);
    },
  },
};
</script>

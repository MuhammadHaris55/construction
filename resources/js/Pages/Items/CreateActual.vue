<template>
  <app-layout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Add Actual
      </h2>
    </template>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-4">
      <div class="">
        <form @submit.prevent="form.post(route('items.store'))">
          <div class="p-2 mr-2 mb-2 mt-4 ml-6 flex flex-wrap">
            <label class="my-2 mr-8 text-right w-36 font-bold">Trade :</label>
            <input
              type="text"
              readonly
              :value="this.item.trade_name"
              class="
                pr-2
                pb-2
                w-full
                lg:w-1/4
                rounded-md
                placeholder-indigo-300
              "
              label="trade"
              placeholder="Enter trade:"
            />
            <!-- <div v-if="errors.revenue">{{ errors.revenue }}</div> -->
          </div>

          <div class="p-2 mr-2 mb-2 mt-4 ml-6 flex flex-wrap">
            <label class="my-2 mr-8 text-right w-36 font-bold"
              >Start Date :</label
            >
            <input
              type="date"
              v-model="form.start"
              readonly
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
              readonly
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

          <div
            v-if="this.reven > 0"
            class="p-2 mr-2 mb-2 mt-4 ml-6 flex flex-wrap"
          >
            <label class="my-2 mr-8 text-right w-36 font-bold">Revenue :</label>
            <input
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

          <div
            v-if="this.cos > 0"
            class="p-2 mr-2 mb-2 mt-4 ml-6 flex flex-wrap"
          >
            <label class="my-2 mr-8 text-right w-36 font-bold">Cost :</label>
            <input
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
              class="
                border
                rounded-xl
                px-4
                py-2
                ml-4
                mt-4
                bg-green-500
                hover:text-white hover:bg-green-600
              "
              type="submit"
            >
              Create Actual
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
import { useForm } from "@inertiajs/inertia-vue3";

export default {
  components: {
    AppLayout,
    Multiselect,
  },

  props: {
    errors: Object,
    item: Object,
    // projects: Array,
    // project: Object,
  },

  data() {
    return {
      reven: this.item.revenue,
      cos: this.item.cost,
    };
  },

  setup(props) {
    const form = useForm({
      start: props.item.start,
      end: props.item.end,
      revenue: props.item.revenue,
      cost: props.item.cost,
      actual: 1,
      trade_id: props.item.trade_id,
      parent_id: props.item.parent_id,
    });
    return { form };
  },
};
</script>

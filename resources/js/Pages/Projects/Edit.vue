<template>
  <app-layout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Edit Project
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
              placeholder="Enter name:"
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
            <label class="my-2 mr-8 text-right w-36 font-bold">Address :</label>
            <textarea
              type="text"
              v-model="form.address"
              class="
                pr-2
                pb-2
                w-full
                lg:w-1/4
                rounded-md
                placeholder-indigo-300
              "
              label="address"
              placeholder="Enter address:"
            />
            <div v-if="errors.address">{{ errors.address }}</div>
          </div>

          <div class="p-2 mr-2 mb-2 mt-4 ml-6 flex flex-wrap">
            <label class="my-2 mr-8 text-right w-36 font-bold"
              >Start Date :</label
            >
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
              label="start"
              placeholder="Select Start Date :"
            />
            <div v-if="errors.start">{{ errors.start }}</div>
          </div>

          <div class="p-2 mr-2 mb-2 mt-4 ml-6 flex flex-wrap">
            <label class="my-2 mr-8 text-right w-36 font-bold"
              >End Date :</label
            >
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
              label="phone"
              placeholder="Select Start Date:"
            />
            <div v-if="errors.end">{{ errors.end }}</div>
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
              Update Project
            </button>
            <!-- <button
              class="border bg-indigo-300 rounded-xl px-4 py-2 ml-4 mt-4"
              type="submit"
            >
              Update Project
            </button> -->
          </div>
        </form>
      </div>
    </div>
  </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import Label from "../../Jetstream/Label.vue";

export default {
  components: {
    AppLayout,
  },

  props: {
    errors: Object,
    project: Object,
  },

  data() {
    return {
      form: this.$inertia.form({
        name: this.project.name,
        address: this.project.address,
        start: this.project.start,
        end: this.project.end,
      }),
    };
  },

  methods: {
    submit() {
      this.$inertia.put(route("projects.update", this.project.id), this.form);
    },
  },
};
</script>

<template>
  <app-layout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Trades
        <div
          style="display: inline-block; min-width: 25%"
          class="flex-1 inline-block float-right"
        >
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
      v-if="$page.props.flash.success"
      class="bg-green-600 text-white text-center"
    >
      {{ $page.props.flash.success }}
    </div>
    <div
      v-if="$page.props.flash.warning"
      class="bg-yellow-600 text-white text-center"
    >
      {{ $page.props.flash.warning }}
    </div>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-4">
      <jet-button @click="create" class="mt-4 ml-2">Create</jet-button>
      <input
        type="search"
        v-if="trade"
        v-model="params.search"
        aria-label="Search"
        placeholder="Search by name..."
        class="pr-2 pb-2 w-full lg:w-1/4 ml-6 rounded-md placeholder-indigo-300"
      />
      <div class="">
        <table class="w-full shadow-lg border mt-4 ml-2 rounded-xl">
          <thead>
            <tr class="bg-gray-600 text-white">
              <th class="py-2 px-4 border">
                <span @click="sort('name')">
                  Name
                  <!-- Name Descending  Starts-->
                  <svg
                    version="1.1"
                    v-if="params.field == 'name' && params.direction == 'desc'"
                    id="Capa_1"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    x="0px"
                    y="0px"
                    width="20px"
                    height="20px"
                    class="inline ml-4"
                    viewBox="0 0 97.761 97.762"
                    style="enable-background: new 0 0 97.761 97.762"
                    xml:space="preserve"
                  >
                    <g>
                      <g>
                        <path
                          d="M42.761,65.596H34.75V2c0-1.105-0.896-2-2-2H16.62c-1.104,0-2,0.895-2,2v63.596H6.609c-0.77,0-1.472,0.443-1.804,1.137
			c-0.333,0.695-0.237,1.519,0.246,2.117l18.076,26.955c0.38,0.473,0.953,0.746,1.558,0.746s1.178-0.273,1.558-0.746L44.319,68.85
			c0.482-0.6,0.578-1.422,0.246-2.117C44.233,66.039,43.531,65.596,42.761,65.596z"
                        />
                        <path
                          d="M93.04,95.098L79.71,57.324c-0.282-0.799-1.038-1.334-1.887-1.334h-3.86c-0.107,0-0.213,0.008-0.318,0.024
			c-0.104-0.018-0.21-0.024-0.318-0.024h-3.76c-0.849,0-1.604,0.535-1.887,1.336L54.403,95.1c-0.215,0.611-0.12,1.289,0.255,1.818
			s0.983,0.844,1.633,0.844h5.773c0.88,0,1.657-0.574,1.913-1.416l2.536-8.324h14.419l2.536,8.324
			c0.256,0.842,1.033,1.416,1.913,1.416h5.771c0.649,0,1.258-0.314,1.633-0.844C93.16,96.387,93.255,95.709,93.04,95.098z
			 M68.905,80.066c2.398-7.77,4.021-13.166,4.82-16.041l4.928,16.041H68.905z"
                        />
                        <path
                          d="M87.297,34.053H69.479L88.407,6.848c0.233-0.336,0.358-0.734,0.358-1.143V2.289c0-1.104-0.896-2-2-2H60.694
			c-1.104,0-2,0.896-2,2v3.844c0,1.105,0.896,2,2,2h16.782L58.522,35.309c-0.233,0.336-0.358,0.734-0.358,1.146v3.441
			c0,1.105,0.896,2,2,2h27.135c1.104,0,2-0.895,2-2v-3.842C89.297,34.947,88.402,34.053,87.297,34.053z"
                        />
                      </g>
                    </g>
                  </svg>
                  <!-- Name Descending Ends-->

                  <!-- Name Ascending  Starts-->
                  <svg
                    v-if="params.field === 'name' && params.direction === 'asc'"
                    version="1.1"
                    id="Capa_1"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    x="0px"
                    y="0px"
                    class="inline ml-4"
                    width="20px"
                    height="20px"
                    viewBox="0 0 97.68 97.68"
                    style="enable-background: new 0 0 97.68 97.68"
                    xml:space="preserve"
                  >
                    <g>
                      <g>
                        <path
                          d="M42.72,65.596h-8.011V2c0-1.105-0.896-2-2-2h-16.13c-1.104,0-2,0.895-2,2v63.596H6.568c-0.77,0-1.472,0.443-1.804,1.137
			C4.432,67.428,4.528,68.25,5.01,68.85l18.076,26.955c0.38,0.473,0.953,0.746,1.558,0.746s1.178-0.273,1.558-0.746L44.278,68.85
			c0.482-0.6,0.578-1.422,0.246-2.117C44.192,66.039,43.49,65.596,42.72,65.596z"
                        />
                        <path
                          d="M92.998,39.315L79.668,1.541c-0.282-0.799-1.038-1.334-1.886-1.334h-3.861c-0.106,0-0.213,0.008-0.317,0.025
			c-0.104-0.018-0.21-0.025-0.318-0.025h-3.76c-0.85,0-1.605,0.535-1.888,1.336L54.362,39.317c-0.215,0.611-0.12,1.289,0.255,1.818
			c0.375,0.529,0.982,0.844,1.632,0.844h5.774c0.88,0,1.656-0.574,1.913-1.416l2.535-8.324H80.89l2.536,8.324
			c0.256,0.842,1.033,1.416,1.913,1.416h5.771c0.648,0,1.258-0.314,1.633-0.844C93.119,40.604,93.213,39.926,92.998,39.315z
			 M68.864,24.283c2.397-7.77,4.02-13.166,4.82-16.041l4.928,16.041H68.864z"
                        />
                        <path
                          d="M87.255,89.838H69.438l18.928-27.205c0.232-0.336,0.357-0.734,0.357-1.143v-3.416c0-1.104-0.896-2-2-2h-26.07
			c-1.104,0-2,0.896-2,2v3.844c0,1.105,0.896,2,2,2h16.782L58.481,91.094c-0.234,0.336-0.359,0.734-0.359,1.145v3.441
			c0,1.105,0.896,2,2,2h27.135c1.104,0,2-0.895,2-2v-3.842C89.255,90.732,88.361,89.838,87.255,89.838z"
                        />
                      </g>
                    </g>
                  </svg>
                  <!-- Name Ascending Ends-->
                </span>
              </th>
              <th class="py-2 px-4 border">Start Date</th>
              <th class="py-2 px-4 border">End Date</th>
              <th class="py-2 px-4 border">Revenue</th>
              <th class="py-2 px-4 border">Cost</th>
              <th class="py-2 px-4 border">Actual</th>
              <th class="py-2 px-4 border">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in balances.data" :key="item.id">
              <td class="py-1 px-4 border">
                {{ item.name }}
              </td>
              <td class="py-1 px-4 border text-center">
                {{ item.start }}
              </td>
              <td class="py-1 px-4 border text-center">
                {{ item.end }}
              </td>
              <td class="py-1 px-4 border">
                {{ item.revenue }}
              </td>
              <td class="py-1 px-4 border">
                {{ item.cost }}
              </td>
              <td class="py-1 px-4 border">
                {{ item.actual }}
              </td>

              <td class="py-1 px-4 border text-center" style="width: 25%">
                <Link
                  class="
                    border
                    rounded-xl
                    px-4
                    py-1
                    m-1
                    bg-blue-400
                    hover:text-white hover:bg-blue-600
                  "
                  @click="edit(item.id)"
                >
                  <span>Edit</span>
                </Link>
              </td>
            </tr>
            <tr v-if="balances.data.length === 0">
              <td class="border-t px-6 py-4" colspan="4">No Record found.</td>
            </tr>
          </tbody>
        </table>
        <paginator class="mt-6" :balances="balances" />
      </div>
    </div>
  </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import JetButton from "@/Jetstream/Button";
import Paginator from "@/Layouts/Paginator";
import { pickBy } from "lodash";
import { throttle } from "lodash";
import Multiselect from "@suadelabs/vue3-multiselect";
import { Head, Link } from "@inertiajs/inertia-vue3";

export default {
  components: {
    AppLayout,
    JetButton,
    Paginator,
    throttle,
    pickBy,
    Multiselect,
    Link,
    Head,
  },

  props: {
    balances: Array,
    filters: Object,
    trade: Object,
    projects: Object,
    projchange: Object,
  },

  data() {
    return {
      // proj_id: this.$page.props.proj_id,
      options: this.projects,
      proj_id: this.projchange,

      params: {
        search: this.filters.search,
        field: this.filters.field,
        direction: this.filters.direction,
      },
    };
  },

  methods: {
    create() {
      this.$inertia.get(route("trades.create"));
    },

    edit(id) {
      this.$inertia.get(route("trades.edit", id));
    },

    destroy(id) {
      this.$inertia.delete(route("trades.destroy", id));
    },

    projch() {
      this.$inertia.get(route("projects.projch", this.proj_id));
    },

    sort(field) {
      this.params.field = field;
      this.params.direction = this.params.direction === "asc" ? "desc" : "asc";
    },
  },
  watch: {
    params: {
      handler: throttle(function () {
        let params = pickBy(this.params);
        this.$inertia.get(this.route("trades"), params, {
          replace: true,
          preserveState: true,
        });
      }, 150),
      deep: true,
    },
  },
};
</script>

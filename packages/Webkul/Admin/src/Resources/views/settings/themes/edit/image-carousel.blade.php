<v-image-carousel :errors="errors">
    <x-admin::shimmer.settings.themes.image-carousel />
</v-image-carousel>

<!-- Image Carousel Vue Component -->
@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-image-carousel-template"
    >
        <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-x-2.5">
                    <div class="flex flex-col gap-1">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.settings.themes.edit.slider')
                        </p>
                        
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300">
                            @lang('admin::app.settings.themes.edit.slider-description')
                        </p>
                    </div>

                    <!-- Add Slider Button -->
                    <div
                        class="secondary-button"
                        @click="$refs.addSliderModal.toggle()"
                    >
                        @lang('admin::app.settings.themes.edit.slider-add-btn')
                    </div>
                </div>

                <template v-for="(deletedSlider, index) in deletedSliders">
                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[deleted_sliders]['+ index +'][image]'"
                        :value="deletedSlider.image"
                    />
                </template>

                <div
                    class="grid pt-4"
                    v-if="sliders.images.length"
                    v-for="(image, index) in sliders.images"
                >
                    <!-- Hidden Input -->
                    <input
                        type="file"
                        class="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][image]'"
                        :ref="'imageInput_' + index"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][title]'"
                        :value="image.title"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][link]'"
                        :value="image.link"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][description]'"
                        :value="image.description"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][button_text]'"
                        :value="image.button_text"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][alignment]'"
                        :value="image.alignment"
                    />

                    <input
                        type="hidden"
                        :name="'{{ $currentLocale->code }}[options]['+ index +'][image]'"
                        :value="image.image"
                    />
                
                    <!-- Details -->
                    <div 
                        class="flex cursor-pointer justify-between gap-2.5 py-5"
                        :class="{
                            'border-b border-slate-300 dark:border-gray-800': index < sliders.images.length - 1
                        }"
                    >
                        <div class="flex gap-2.5">
                            <div class="grid place-content-start gap-1.5">
                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.settings.themes.edit.image-title'): 

                                    <span class="text-gray-600 transition-all dark:text-gray-300">
                                        @{{ image.title }}
                                    </span>
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.settings.themes.edit.link'): 

                                    <span class="text-gray-600 transition-all dark:text-gray-300">
                                        @{{ image.link }}
                                    </span>
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    Description: 

                                    <span class="text-gray-600 transition-all dark:text-gray-300">
                                        @{{ image.description }}
                                    </span>
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    Button Text: 

                                    <span class="text-gray-600 transition-all dark:text-gray-300">
                                        @{{ image.button_text }}
                                    </span>
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    Content Position: 

                                    <span class="text-gray-600 transition-all dark:text-gray-300">
                                        @{{ image.alignment || 'left' }}
                                    </span>
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.settings.themes.edit.image'): 

                                    <span class="text-gray-600 transition-all dark:text-gray-300">
                                        <a
                                            :href="'{{ config('app.url') }}/' + image.image"
                                            :ref="'image_' + index"
                                            target="_blank"
                                            class="text-blue-600 transition-all hover:underline ltr:ml-2 rtl:mr-2"
                                        >
                                            <span :ref="'imageName_' + index">
                                                @{{ image.image }}
                                            </span>
                                        </a>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="grid place-content-start gap-1 text-right">
                            <p 
                                class="cursor-pointer text-blue-600 transition-all hover:underline"
                                @click="openEdit(image, index)"
                            > 
                                Edit
                            </p>
                            <p 
                                class="cursor-pointer text-red-600 transition-all hover:underline"
                                @click="remove(image)"
                            > 
                                @lang('admin::app.settings.themes.edit.delete')
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Empty Page -->
                <div
                    class="grid justify-center justify-items-center gap-3.5 px-2.5 py-10"
                    v-else
                >
                    <img
                        class="h-[120px] w-[120px] p-2 dark:mix-blend-exclusion dark:invert"
                        src="{{ bagisto_asset('images/empty-placeholders/default.svg') }}"
                        alt="@lang('admin::app.settings.themes.edit.slider')"
                    >

                    <div class="flex flex-col items-center gap-1.5">
                        <p class="text-base font-semibold text-gray-400">
                            @lang('admin::app.settings.themes.edit.slider-add-btn')
                        </p>
                        
                        <p class="text-gray-400">
                            @lang('admin::app.settings.themes.edit.slider-description')
                        </p>
                    </div>
                </div>
            </div>

            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
            >
                <form 
                    @submit="handleSubmit($event, saveSliderImage)"
                    enctype="multipart/form-data"
                    ref="createSliderForm"
                >
                    <x-admin::modal ref="addSliderModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <p class="text-lg font-bold text-gray-800 dark:text-white">
                                @lang('admin::app.settings.themes.edit.update-slider')
                            </p>
                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.themes.edit.image-title')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="{{ $currentLocale->code }}[title]"
                                    rules="required"
                                    :placeholder="trans('admin::app.settings.themes.edit.image-title')"
                                    :label="trans('admin::app.settings.themes.edit.image-title')"
                                />

                                <x-admin::form.control-group.error control-name="{{ $currentLocale->code }}[title]" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.themes.edit.link')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="{{ $currentLocale->code }}[link]"
                                    :placeholder="trans('admin::app.settings.themes.edit.link')"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    Description
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="textarea"
                                    name="{{ $currentLocale->code }}[description]"
                                    :placeholder="'Description'"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    Button Text
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="{{ $currentLocale->code }}[button_text]"
                                    :placeholder="'Shop Now'"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    Content Position
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="{{ $currentLocale->code }}[alignment]"
                                >
                                    <option value="left">Left</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                </x-admin::form.control-group.control>
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.themes.edit.slider-image')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="image"
                                    name="slider_image"
                                    rules="required"
                                    :is-multiple="false"
                                />

                                <x-admin::form.control-group.error control-name="slider_image" />
                            </x-admin::form.control-group>

                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                @lang('admin::app.settings.themes.edit.image-size')
                            </p>
                        </x-slot>

                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <!-- Save Button -->
                            <x-admin::button
                                button-type="submit"
                                class="primary-button justify-center"
                                :title="trans('admin::app.settings.themes.edit.save-btn')"
                            />
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>

            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
            >
                <form 
                    @submit="handleSubmit($event, updateSliderImage)"
                    enctype="multipart/form-data"
                    ref="updateSliderForm"
                >
                    <x-admin::modal ref="editSliderModal">
                        <x-slot:header>
                            <p class="text-lg font-bold text-gray-800 dark:text-white">
                                Edit Slider
                            </p>
                        </x-slot>

                        <x-slot:content>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.themes.edit.image-title')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="{{ $currentLocale->code }}[title]"
                                    rules="required"
                                    :placeholder="trans('admin::app.settings.themes.edit.image-title')"
                                    :label="trans('admin::app.settings.themes.edit.image-title')"
                                    ::value="editing.title"
                                />

                                <x-admin::form.control-group.error control-name="{{ $currentLocale->code }}[title]" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.themes.edit.link')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="{{ $currentLocale->code }}[link]"
                                    :placeholder="trans('admin::app.settings.themes.edit.link')"
                                    ::value="editing.link"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    Description
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="textarea"
                                    name="{{ $currentLocale->code }}[description]"
                                    :placeholder="'Description'"
                                    ::value="editing.description"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    Button Text
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="{{ $currentLocale->code }}[button_text]"
                                    :placeholder="'Shop Now'"
                                    ::value="editing.button_text"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    Content Position
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="{{ $currentLocale->code }}[alignment]"
                                    ::value="editing.alignment"
                                >
                                    <option value="left">Left</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                </x-admin::form.control-group.control>
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.themes.edit.slider-image')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="image"
                                    name="slider_image"
                                    :is-multiple="false"
                                />

                                <x-admin::form.control-group.error control-name="slider_image" />
                            </x-admin::form.control-group>

                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                @lang('admin::app.settings.themes.edit.image-size')
                            </p>
                        </x-slot>

                        <x-slot:footer>
                            <x-admin::button
                                button-type="submit"
                                class="primary-button justify-center"
                                :title="trans('admin::app.settings.themes.edit.save-btn')"
                            />
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
        app.component('v-image-carousel', {
            template: '#v-image-carousel-template',

            props: ['errors'],

            data() {
                return {
                    sliders: @json($theme->translate($currentLocale->code)['options'] ?? null),

                    deletedSliders: [],
                    editingIndex: null,
                    editing: {
                        title: '',
                        link: '',
                        description: '',
                        button_text: '',
                        alignment: 'left',
                    },
                };
            },
            
            created() {
                if (
                    this.sliders == null 
                    || this.sliders.length == 0
                ) {
                    this.sliders = { images: [] };
                }   
            },

            methods: {
                saveSliderImage(params, { resetForm ,setErrors }) {
                    let formData = new FormData(this.$refs.createSliderForm);

                    try {
                        const sliderImage = formData.get("slider_image[]");

                        if (! sliderImage) {
                            throw new Error("{{ trans('admin::app.settings.themes.edit.slider-required') }}");
                        }

                        this.sliders.images.push({
                            title: formData.get("{{ $currentLocale->code }}[title]"),
                            description: formData.get("{{ $currentLocale->code }}[description]"),
                            button_text: formData.get("{{ $currentLocale->code }}[button_text]"),
                            alignment: formData.get("{{ $currentLocale->code }}[alignment]") || 'left',
                            link: formData.get("{{ $currentLocale->code }}[link]"),
                            slider_image: sliderImage,
                        });

                        if (sliderImage instanceof File) {
                            this.setFile(sliderImage, this.sliders.images.length - 1);
                        }

                        resetForm();

                        this.$refs.addSliderModal.toggle();
                    } catch (error) {
                        setErrors({'slider_image': [error.message]});
                    }
                },

                openEdit(image, index) {
                    this.editingIndex = index;

                    this.editing = {
                        title: image.title || '',
                        link: image.link || '',
                        description: image.description || '',
                        button_text: image.button_text || '',
                        alignment: image.alignment || 'left',
                    };

                    this.$refs.editSliderModal.toggle();
                },

                updateSliderImage(params, { resetForm ,setErrors }) {
                    try {
                        const formData = new FormData(this.$refs.updateSliderForm);

                        const index = this.editingIndex;

                        if (index === null || typeof this.sliders.images[index] === 'undefined') {
                            throw new Error('Invalid slider index');
                        }

                        const sliderImage = formData.get("slider_image[]");

                        const updated = this.sliders.images[index];

                        updated.title = formData.get("{{ $currentLocale->code }}[title]") || '';
                        updated.link = formData.get("{{ $currentLocale->code }}[link]") || '';
                        updated.description = formData.get("{{ $currentLocale->code }}[description]") || '';
                        updated.button_text = formData.get("{{ $currentLocale->code }}[button_text]") || '';
                        updated.alignment = formData.get("{{ $currentLocale->code }}[alignment]") || 'left';

                        if (sliderImage instanceof File) {
                            updated.slider_image = sliderImage;
                            this.setFile(sliderImage, index);
                        }

                        resetForm();

                        this.editingIndex = null;

                        this.$refs.editSliderModal.toggle();
                    } catch (error) {
                        setErrors({'slider_image': [error.message]});
                    }
                },

                setFile(file, index) {
                    let dataTransfer = new DataTransfer();

                    dataTransfer.items.add(file);

                    setTimeout(() => {
                        this.$refs['image_' + index][0].href =  URL.createObjectURL(file);

                        this.$refs['imageName_' + index][0].innerHTML = file.name;

                        this.$refs['imageInput_' + index][0].files = dataTransfer.files;
                    }, 0);
                },

                remove(image) {
                    this.$emitter.emit('open-confirm-modal', {
                        agree: () => {
                            this.deletedSliders.push(image);
                    
                            this.sliders.images = this.sliders.images.filter(item => {
                                return (
                                    item.title !== image.title || 
                                    item.description !== image.description || 
                                    item.button_text !== image.button_text || 
                                    item.alignment !== image.alignment || 
                                    item.link !== image.link || 
                                    item.image !== image.image
                                );
                            });
                        }
                    });
                },
            },
        });
    </script>
@endPushOnce    

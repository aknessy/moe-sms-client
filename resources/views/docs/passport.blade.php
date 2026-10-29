<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <div class="w-full flex flex-col bg-transparent">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <nav aria-label="breadcrumb" class="w-full sm:w-1/3 space-y-2">
                    <ol class="flex w-full flex-wrap items-center gap-1.5 py-2 text-xs">
                        <li class="flex cursor-pointer items-center gap-1.5 text-base-content/60 transition-colors duration-300 hover:text-[#2E7D32]">
                            <a href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                </svg>
                            </a>
                            <span class="pointer-events-none text-base-content/30">/</span>
                        </li>
                        <li class="flex items-center text-base-content/60 transition-colors duration-300 cursor-not-allowed">
                            <a href="#">Documents</a>
                        </li>
                    </ol>
                </nav>
                <div class="w-full sm:w-2/3">
                    <h5 class="font-alkalami text-sm font-semibold uppercase tracking-wide flex justify-end m-0">Add Staff Documents</h5>
                </div>
            </div>
            <div>
                <form type="post" accept-charset="" action="" enctype="multipart/form-data" class="flex w-full flex-col gap-y-4 py-4 lg:flex-row lg:gap-x-8 lg:gap-y-0">
                    @csrf

                    <div class="w-full flex flex-col lg:w-1/3">
                        <div class="w-full flex flex-col bg-white px-4 py-4 border border-base-300 rounded-md">
                            <x-docs-sidebar activePage="{{ $active_page }}" :uuid="$uri_segments[3]" />
                        </div>
                    </div>
                    <div class="w-full lg:w-2/3 px-4 py-2 bg-white border border-base-300 rounded-lg">
                        <div class="flex flex-col items-start border-b border-b-brown-100">
                            <h2 class="font-alkalami text-base font-semibold mb-0 text-base-content/80">Upload Passport</h2>
                            <p class="font-sans text-sm font-normal text-base-content/60 m-0">Upload passport for <small><em>staff name</em></small></p>
                        </div>
                        <div class="py-4 block bg-contain bg-right-top bg-no-repeat" style="background-image: url('{{Vite::asset('resources/images/bg-state-empty.png')}}');background-size: 300px">
                            <div class="form-input-area py-3 px-3 mt-3 gap-y-4">
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="mb-3 w-full lg:flex flex-col items-center lg:space-x-4 space-x-2">
                                        <div class="relative mx-auto w-28 h-28 rounded-full overflow-hidden mb-3 bg-white ring ring-base-300 shadow-lg">
                                            <img id="previewPassport" src="" class="w-full h-full object-cover text-center" alt="Passport Preview" />
                                        </div>
                                        <div class="w-full">
                                            <label class="font-semibold font-sans text-sm text-base-content">Select Passport</label>
                                            <div class="relative">
                                                <input
                                                    id="passport"
                                                    name="passport"
                                                    type="file"
                                                    placeholder="Pick Image"
                                                    class="file-input file-input-bordered w-full max-w-x bg-white text-xs"
                                                    accept="image/*" size=""/>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                    <path d="M15 10l-4 4l6 6l4 -16l-18 7l4 2l2 6l3 -4"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="py-3 px-4 flex flex-wrap items-center justify-end gap-2">
                                    <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
                                        Upload Passport
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
<script>
    $(function(){
        $("#passport").change(function() {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $("#previewPassport").attr("src", e.target.result).show();
                }
                reader.readAsDataURL(file);
            }
        });
    })
</script>


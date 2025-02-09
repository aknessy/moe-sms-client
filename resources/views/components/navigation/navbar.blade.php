<div class="flex items-center justify-between h-16bg-brown-50 border-b border-brown-100">
	<div class="flex items-center pl-4 pt-2 pb-2 gap-x-4">
		<label for="menu-toggle"
			class="md:hidden mr-4 bg-gray-800 text-white p-2 rounded focus:outline-none cursor-pointer">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none"
				viewBox="0 0 24 24" stroke="white">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
					d="M4 6h16M4 12h16M4 18h16" />
			</svg>
		</label>
		<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
			<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
		</svg>
    	<span class="text-xl font-bold text-gray-800">Welcome, {{ $name ?? 'Guest' }}!</span>
	</div>
	<div class="flex items-center pr-4 pt-2 pb-2">
		<div class="relative inline-block text-left">
			<div>
			  	<button onclick="toggleDropDownMenu('.dropdown-menu')" type="button" class="inline-flex w-full justify-center items-center gap-x-1.5 rounded-md bg-slate-700 px-3 py-2 text-sm font-semibold text-gray-50 ring-1 shadow-xs ring-slate-800 ring-inset hover:bg-slate-900" id="menu-button" aria-expanded="false" aria-haspopup="true">
					<span>Options</span>
					<img src="{{ Vite::asset('resources/images/avatars/avatar-1.png') }}" alt="Avatar" class="rounded-full size-5 border bordr-white" />
					<svg class="-mr-1 size-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
				  		<path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
					</svg>
			  	</button>
			</div>
			<div id="dropdown-menu" class="dropdown-menu hidden absolute right-0 z-10 divide-y divide-gray-900 mt-2 w-56 origin-top-right rounded-md bg-slate-700 ring-1 shadow-lg ring-gray-100/5 focus:outline-hidden" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
			  	<div class="py-1" role="none">
					<!-- Active: "bg-gray-100 text-gray-900 outline-hidden", Not Active: "text-gray-700" -->
					<a href="#" class="block items-centerpx-4 py-2 px-3 text-sm text-gray-50 hover:opacity-40 active:bg-opacity-40 focus:bg-opacity-40 hover:text-gray-200 focus:text-gray-200 active:text-gray-200" role="menuitem" tabindex="-1" id="menu-item-0">
						<div class="flex items-center gap-x-2">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2"> <path d="M19.875 6.27a2.225 2.225 0 0 1 1.125 1.948v7.284c0 .809 -.443 1.555 -1.158 1.948l-6.75 4.27a2.269 2.269 0 0 1 -2.184 0l-6.75 -4.27a2.225 2.225 0 0 1 -1.158 -1.948v-7.285c0 -.809 .443 -1.554 1.158 -1.947l6.75 -3.98a2.33 2.33 0 0 1 2.25 0l6.75 3.98h-.033z"></path> <path d="M15.5 9.422c.312 .18 .503 .515 .5 .876v3.277c0 .364 -.197 .7 -.515 .877l-3 1.922a1 1 0 0 1 -.97 0l-3 -1.922a1 1 0 0 1 -.515 -.876v-3.278c0 -.364 .197 -.7 .514 -.877l3 -1.79c.311 -.174 .69 -.174 1 0l3 1.79h-.014z"></path> </svg>
							<span>Profile settings</span>
						</div>
					</a>
					<a href="#" class="block px-3 py-2 text-sm text-gray-50 hover:opacity-40 active:bg-opacity-40 focus:bg-opacity-40 hover:text-gray-200 focus:text-gray-200 active:text-gray-200" role="menuitem" tabindex="-1" id="menu-item-1">
						<div class="flex items-center gap-x-2">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
								<path d="M20.993 11.63a9 9 0 0 1 -9.362 9.362l9.362 -9.362z"></path>
								<path d="M12 3a9.001 9.001 0 0 1 8.166 5.211l-11.955 11.955a9 9 0 0 1 3.789 -17.166z"></path>
								<path d="M12 12l-6 6"></path>
							  </svg>
							<span>Analysis</span>
						</div>
					</a>
			  	</div>
				<div class="py-1" role="none">
					<form method="POST" action="#" role="none">
						@csrf
						<button type="submit" class="block w-full px-4 py-2 text-left text-sm text-gray-50 hover:opacity-25 active:bg-opacity-35 focus:bg-opacity-40 hover:text-gray-200 focus:text-gray-200 active:text-gray-200" role="menuitem" tabindex="-1" id="menu-item-3">Sign out</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>


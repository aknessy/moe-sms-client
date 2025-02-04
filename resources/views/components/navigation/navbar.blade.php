<div class="flex items-center justify-between h-16 bg-brown-50 border-b border-brown-100">
	<div class="flex items-center px-4">
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
		<input class="mx-4 w-full border rounded-md px-4 py-2" type="text" placeholder="Search">
	</div>
	<div class="flex items-center pr-4">
		<ul class="menu lg:menu-horizontal bg-base-200 rounded-box lg:mb-64">
			<li><a>Item 1</a></li>
			<li>
			  <details>
				<summary>Parent item</summary>
				<ul>
				  <li><a>Submenu 1</a></li>
				  <li><a>Submenu 2</a></li>
				  <li>
					<details open>
					  <summary>Parent</summary>
					  <ul>
						<li><a>item 1</a></li>
						<li><a>item 2</a></li>
					  </ul>
					</details>
				  </li>
				</ul>
			  </details>
			</li>
			<li><a>Item 3</a></li>
		  </ul>
	</div>
</div>
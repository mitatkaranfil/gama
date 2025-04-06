<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <nav class="mb-4">
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                        <x-nav-link :href="route('tasks')" :active="request()->routeIs('tasks')">
                            {{ __('Tasks') }}
                        </x-nav-link>
                        <x-nav-link :href="route('daily_tasks')" :active="request()->routeIs('daily_tasks')">
                            {{ __('Daily Tasks') }}
                        </x-nav-link>
                        <x-nav-link :href="route('users')" :active="request()->routeIs('users')">
                            {{ __('Users') }}
                        </x-nav-link>
                    </nav>

                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
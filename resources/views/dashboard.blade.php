<x-layouts::app :title="__('Dashboard')">
    @can('superadminOrAdmin')
        <livewire:admin.dashboard />
    @endcan
    @can('reviewer')
        <livewire:reviewers.dashboard />
    @endcan
    @can('user')
        <livewire:user.dashboard />
    @endcan
</x-layouts::app>

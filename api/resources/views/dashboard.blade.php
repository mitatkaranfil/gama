@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Admin Dashboard</h1>
    <p>Total Users: {{ $userCount }}</p>
    <p>Total Tasks: {{ $taskCount }}</p>
    <p>Total Daily Tasks: {{ $dailyTaskCount }}</p>
@endsection
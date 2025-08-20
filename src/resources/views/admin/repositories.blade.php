@extends('layouts.admin')

@section('title', 'リポジトリ管理')

@section('content')
<div class="px-4 sm:px-0">
    @livewire('repository-manager')
</div>
@endsection

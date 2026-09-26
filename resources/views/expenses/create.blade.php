@extends('layouts.app')

@section('title', 'New Expense')
@section('page-title', 'Record Expense')
@section('page-subtitle', 'Log a business expense')

@section('content')
    @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            <ul class="list-disc ml-5">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
            @include('expenses._form')
        </form>
    </div>
@endsection

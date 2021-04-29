@if(request()->get('sort') === $column)
    @php ($dir = '▲')
    @php ($params = array_merge(request()->query(), ['sort' => '-' . $column]))
@elseif(request()->get('sort') === '-' . $column)
    @php ($dir = '▼')
    @php ($params = request()->except(['sort']))
@else
    @php ($dir = '')
    @php ($params = array_merge(request()->query(), ['sort' => $column]))
@endif

<a href="{{ route('admin.servers', $params) }}">{{ $dir }} {{ $name }}</a>

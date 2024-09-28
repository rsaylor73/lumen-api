@extends('email_template.layout')

@section('content')
    <tr>
        <td>
            <b>Summary</b>
            <hr>

            <ul>
                @foreach($error_summary as $key => $value)
                    <li>
                        {{ $key }} :
                        @if($value == "true")
                            <font color="red">One or more errors.</font>
                        @else
                            <font color="green">No errors detected.</font>
                        @endif
                    </li>
                @endforeach
            </ul>

            {!! $html !!}

        </td>
   </tr>
@endsection

<section>

    <h3 class="h5 fw-bold mb-2">

        @if($featureIcon)
            {{ $featureIcon }}
        @endif

        {{ $featureTitle }}

    </h3>


    @if($featureDescription)

        <p class="mb-3">
            {{ $featureDescription }}
        </p>

    @endif


    @if(
        is_array($featureDetails)
        && count($featureDetails) > 0
    )

        <div class="border rounded-4 p-3 bg-light">

            @foreach($featureDetails as $detail)

                <div @class([
                    'mb-3' => ! $loop->last,
                ])>

                    @if(
                        ! empty(
                            $detail['title']
                            ?? null
                        )
                    )

                        <div class="fw-bold">
                            {{ $detail['title'] }}
                        </div>

                    @endif


                    @if(
                        ! empty(
                            $detail['text']
                            ?? null
                        )
                    )

                        <div class="small text-muted">
                            {{ $detail['text'] }}
                        </div>

                    @endif

                </div>

            @endforeach

        </div>

    @endif

</section>

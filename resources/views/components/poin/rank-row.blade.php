@props(['row', 'highlight' => false])
<x-poin.player-row size="compact" :highlight="$highlight" :rank="$row->rank" :name="$row->nickname" :meta="$row->plays . ' main · ' . $row->wins . ' menang'" :score="$row->points" />

<?php

namespace ImmiTranslate\Datalab\Enums;

enum DatalabExtra: string
{
    /** Extract tracked changes, e.g. from Word documents. */
    case TrackChanges = 'track_changes';

    /** Enable chart understanding. */
    case ChartUnderstanding = 'chart_understanding';

    /**
     * Per-cell bounding boxes for each table (instead of one box for the whole table);
     * also enables word bounding boxes. Requires the 'html' output format to expose the
     * data-bbox/data-confidence attributes; billed at $0.30 per 1K pages.
     */
    case TableCellBboxes = 'table_cell_bboxes';

    /**
     * Per-item bounding boxes for each list or list group (instead of one box for the
     * whole list); also enables word bounding boxes. Requires the 'html' output format to
     * expose the data-bbox/data-confidence attributes; billed at $0.30 per 1K pages.
     */
    case ListItemBboxes = 'list_item_bboxes';

    /** Extract links from the document. */
    case ExtractLinks = 'extract_links';

    /** Enable infographic handling. */
    case Infographic = 'infographic';

    /** Enable new block types. */
    case NewBlockTypes = 'new_block_types';
}

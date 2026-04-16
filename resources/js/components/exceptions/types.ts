export interface ExceptionTrace {
    /** "app/Http/Kernel.php:42" */
    file: string;
    /** "App\Http\Kernel->handle(Illuminate\Http\Request $request)" */
    source: string;
    code: Record<string, string> | null;
}

export interface ExceptionOccurrence {
    group: string;
    timestamp: string;
    /** Base file path without line */
    file: string;
    line: number;
    class: string;
    message: string;
    handled: boolean;
    /** HTTP / error code, or "0" when absent */
    code: string;
    laravel_version: string;
    php_version: string;
    trace: ExceptionTrace[];
}

/** Internal – trace entry after index is injected */
export interface IndexedTrace extends ExceptionTrace {
    index: number;
}

export interface FrameGroup {
    vendor: boolean;
    frames: IndexedTrace[];
}

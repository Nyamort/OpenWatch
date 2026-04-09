import { useCallback, useEffect, useRef, useState } from 'react';

const BASE_WIDTH = 600;
const MIN_ZOOM = 1;
const MAX_ZOOM = 20;

interface UseTimelineZoomReturn {
    zoomLevel: number;
    containerWidth: number;
    barWidth: string;
    scrollRef: React.RefObject<HTMLDivElement | null>;
    innerRef: React.RefObject<HTMLDivElement | null>;
    ticksInnerRef: React.RefObject<HTMLDivElement | null>;
    handleBarsScroll: (e: React.UIEvent<HTMLDivElement>) => void;
    handleTicksMouseDown: (e: React.MouseEvent) => void;
    handleTicksDoubleClick: () => void;
}

export function useTimelineZoom(): UseTimelineZoomReturn {
    const [zoomLevel, setZoomLevel] = useState(1);
    const [containerWidth, setContainerWidth] = useState(BASE_WIDTH);
    const zoomRef = useRef(1);
    const dragRef = useRef<{ startX: number; startZoom: number } | null>(null);
    const scrollRef = useRef<HTMLDivElement>(null);
    const innerRef = useRef<HTMLDivElement>(null);
    const ticksInnerRef = useRef<HTMLDivElement>(null);

    // Track scroll container width so zoom=1 exactly fills the available space
    useEffect(() => {
        const el = scrollRef.current;
        if (!el) return;
        const observer = new ResizeObserver(([entry]) => {
            const width = entry.contentRect.width;
            if (width > 0) setContainerWidth(width);
        });
        observer.observe(el);
        return () => observer.disconnect();
    }, []);

    // Drag-to-zoom on the ticks header
    useEffect(() => {
        const onMouseMove = (e: MouseEvent) => {
            if (!dragRef.current) return;
            const delta = e.clientX - dragRef.current.startX;
            const newZoom = Math.min(
                MAX_ZOOM,
                Math.max(
                    MIN_ZOOM,
                    dragRef.current.startZoom * (1 + delta / 200),
                ),
            );
            if (newZoom === zoomRef.current) return;

            const ratio = newZoom / zoomRef.current;
            zoomRef.current = newZoom;
            setZoomLevel(newZoom);

            requestAnimationFrame(() => {
                if (scrollRef.current) {
                    scrollRef.current.scrollLeft *= ratio;
                }
            });
        };

        const onMouseUp = () => {
            dragRef.current = null;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        };

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
        return () => {
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
        };
    }, []);

    const handleBarsScroll = useCallback((e: React.UIEvent<HTMLDivElement>) => {
        if (ticksInnerRef.current) {
            ticksInnerRef.current.style.transform = `translateX(-${(e.target as HTMLDivElement).scrollLeft}px)`;
        }
    }, []);

    const handleTicksMouseDown = useCallback((e: React.MouseEvent) => {
        dragRef.current = { startX: e.clientX, startZoom: zoomRef.current };
        document.body.style.cursor = 'ew-resize';
        document.body.style.userSelect = 'none';
    }, []);

    const handleTicksDoubleClick = useCallback(() => {
        zoomRef.current = 1;
        setZoomLevel(1);
        if (scrollRef.current) {
            scrollRef.current.scrollLeft = 0;
        }
    }, []);

    return {
        zoomLevel,
        containerWidth,
        barWidth: `${containerWidth * zoomLevel}px`,
        scrollRef,
        innerRef,
        ticksInnerRef,
        handleBarsScroll,
        handleTicksMouseDown,
        handleTicksDoubleClick,
    };
}

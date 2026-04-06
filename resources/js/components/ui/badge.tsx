import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"
import * as React from "react"

import { cn } from "@/lib/utils"

const badgeVariants = cva(
  "inline-flex w-fit shrink-0 items-center justify-center gap-1 font-mono uppercase transition-colors dark:border [&_svg]:size-2.5 h-6 min-w-5 rounded-md px-1.5 text-xs whitespace-nowrap focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "bg-primary/15 text-primary dark:border-primary/40 dark:bg-primary/20",
        secondary:
          "bg-zinc-200 text-zinc-900 dark:border-zinc-600 dark:bg-zinc-900/70 dark:text-zinc-400",
        destructive:
          "bg-red-200 text-red-900 dark:border-red-600 dark:bg-red-900/70 dark:text-red-400",
        outline:
          "border text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
        success:
          "bg-emerald-200 text-emerald-900 dark:border-emerald-600 dark:bg-emerald-900/70 dark:text-emerald-400",
        warning:
          "bg-amber-200 text-amber-900 dark:border-amber-600 dark:bg-amber-900/70 dark:text-amber-400",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
)

function Badge({
  className,
  variant,
  asChild = false,
  ...props
}: React.ComponentProps<"span"> &
  VariantProps<typeof badgeVariants> & { asChild?: boolean }) {
  const Comp = asChild ? Slot : "span"

  return (
    <Comp
      data-slot="badge"
      className={cn(badgeVariants({ variant }), className)}
      {...props}
    />
  )
}

export { Badge, badgeVariants }

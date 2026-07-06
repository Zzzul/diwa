import { Scalar } from "@scalar/hono-api-reference";
import type { Hono } from "hono";

const spec = {
    openapi: "3.1.0",
    info: {
        title: "Diwa API",
        version: "1.0.0",
        description: "API for DistroWatch data — rankings, distributions, news",
    },
    servers: [{ url: "http://localhost:3000", description: "local dev" }],
    paths: {
        "/api/healthz": {
            get: {
                summary: "Health check",
                responses: {
                    "200": {
                        description: "OK",
                        content: {
                            "application/json": {
                                schema: {
                                    type: "object",
                                    properties: { ok: { type: "boolean" } },
                                },
                            },
                        },
                    },
                },
            },
        },
        "/api/rankings": {
            get: {
                summary: "Rankings list",
                parameters: [
                    {
                        name: "slug",
                        in: "query",
                        schema: { type: "string" },
                        description: "filter by distro slug",
                    },
                    {
                        name: "dataspan",
                        in: "query",
                        schema: { type: "string", default: "26" },
                        description: "time span filter",
                    },
                ],
                responses: { "200": { description: "Rankings" } },
            },
        },
        "/api/rankings/dataspans": {
            get: {
                summary: "Dataspan for filtering rankings",
                responses: { "200": { description: "Dataspans list" } },
            },
        },
        "/api/news": {
            get: {
                summary: "News list",
                parameters: [
                    {
                        name: "type",
                        in: "query",
                        schema: { type: "string" },
                        description: "filter by news type",
                    },
                    {
                        name: "date",
                        in: "query",
                        schema: { type: "string" },
                        description: "filter by date (YYYY-MM-DD)",
                    },
                    {
                        name: "distribution",
                        in: "query",
                        schema: { type: "string" },
                        description: "filter by distro slug",
                    },
                    {
                        name: "release",
                        in: "query",
                        schema: { type: "string" },
                        description:
                            "stable | weekly | development | bsd | mobile | other",
                    },
                    {
                        name: "month",
                        in: "query",
                        schema: { type: "string" },
                        description: "month name (January, February, ...)",
                    },
                    {
                        name: "year",
                        in: "query",
                        schema: { type: "string" },
                        description: "four-digit year",
                    },
                ],
                responses: { "200": { description: "News list" } },
            },
        },
        "/api/news/filters": {
            get: {
                summary: "News filter options",
                description:
                    "Distribution slugs, release types, months, years for filtering news",
                responses: { "200": { description: "Filter options" } },
            },
        },
        "/api/news/detail/{newsid}": {
            get: {
                summary: "News detail by DistroWatch newsid",
                description:
                    "Detailed news including distribution info, related news. Rejects weekly type.",
                parameters: [
                    {
                        name: "newsid",
                        in: "path",
                        required: true,
                        schema: { type: "string" },
                        description: "DistroWatch numeric news ID",
                    },
                ],
                responses: { "200": { description: "News detail" } },
            },
        },
        "/api/news/{id}": {
            get: {
                summary: "News detail",
                parameters: [
                    {
                        name: "id",
                        in: "path",
                        required: true,
                        schema: { type: "string" },
                    },
                ],
                responses: { "200": { description: "News detail" } },
            },
        },
        "/api/distributions": {
            get: {
                summary: "Distribution list",
                responses: {
                    "200": { description: "List of all distributions" },
                },
            },
        },
        "/api/distributions/random": {
            get: {
                summary: "Random distribution",
                responses: {
                    "200": { description: "Random distribution detail" },
                },
            },
        },
        "/api/distributions/{slug}": {
            get: {
                summary: "Distribution detail",
                parameters: [
                    {
                        name: "slug",
                        in: "path",
                        required: true,
                        schema: { type: "string" },
                    },
                ],
                responses: { "200": { description: "Distribution detail" } },
            },
        },
        "/api/distributions/latest": {
            get: {
                summary: "Latest distributions",

                responses: {
                    "200": {
                        description: "Latest distro releases from homepage",
                    },
                },
            },
        },
        "/api/headlines": {
            get: {
                summary: "Latest headlines",

                responses: { "200": { description: "Headlines list" } },
            },
        },
        "/api/packages": {
            get: {
                summary: "Latest packages",

                responses: { "200": { description: "Packages list" } },
            },
        },
        "/api/reviews": {
            get: {
                summary: "Latest reviews",

                responses: { "200": { description: "Reviews list" } },
            },
        },
        "/api/newsletters": {
            get: {
                summary: "Latest newsletters",

                responses: { "200": { description: "Newsletters list" } },
            },
        },
        "/api/podcasts": {
            get: {
                summary: "Latest podcasts",

                responses: { "200": { description: "Podcasts list" } },
            },
        },
        "/api/additions": {
            get: {
                summary: "Latest additions",

                responses: { "200": { description: "Additions list" } },
            },
        },
        "/api/waiting-list": {
            get: {
                summary: "New to waiting list",

                responses: { "200": { description: "Waiting list" } },
            },
        },
        "/api/weekly/{issue}": {
            get: {
                summary: "DistroWatch Weekly issue",
                description:
                    "Full weekly issue content with sections (feature, news, Q&A, etc.)",
                parameters: [
                    {
                        name: "issue",
                        in: "path",
                        required: true,
                        schema: { type: "string" },
                        description:
                            "Issue date in YYYYMMDD format (e.g. 20260629)",
                    },
                ],
                responses: { "200": { description: "Weekly issue" } },
            },
        },
        "/api/search/filters": {
            get: {
                summary: "Search filter options",
                description: "Available filters for distribution search (OS type, category, based on, desktop, etc.)",
                responses: { "200": { description: "Search filters" } },
            },
        },
        "/api/search": {
            get: {
                summary: "Search distributions",
                description: "Search distributions by criteria. All params optional.",
                parameters: [
                    { name: "ostype", in: "query", schema: { type: "string" } },
                    { name: "category", in: "query", schema: { type: "string" } },
                    { name: "origin", in: "query", schema: { type: "string" } },
                    { name: "basedon", in: "query", schema: { type: "string" } },
                    { name: "notbasedon", in: "query", schema: { type: "string" } },
                    { name: "desktop", in: "query", schema: { type: "string" } },
                    { name: "architecture", in: "query", schema: { type: "string" } },
                    { name: "package", in: "query", schema: { type: "string" } },
                    { name: "rolling", in: "query", schema: { type: "string" } },
                    { name: "isosize", in: "query", schema: { type: "string" } },
                    { name: "netinstall", in: "query", schema: { type: "string" } },
                    { name: "language", in: "query", schema: { type: "string" } },
                    { name: "defaultinit", in: "query", schema: { type: "string" } },
                    { name: "status", in: "query", schema: { type: "string" } },
                ],
                responses: { "200": { description: "Search results" } },
            },
        },
    },
};

export function setupOpenApi(app: Hono): void {
    app.get("/api/doc", (c) => c.json(spec));
    app.get(
        "/api/docs",
        Scalar({ url: "/api/doc", pageTitle: "Diwa API Docs" }),
    );
}
